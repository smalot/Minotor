<?php

namespace Smalot\Minotor\Server;

use Socket\Raw\Socket;

/**
 * Class SocketServer
 * @package Smalot\Minotor\Server
 */
class SocketServer
{
    /**
     * @var int
     */
    protected $pid;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var array
     */
    protected $connections = array();

    /**
     * @var int
     */
    protected $maxClients = 25;

    /**
     * @var int
     */
    protected $backLog = 100;

    /**
     * @var int
     */
    protected $timeout = 300;

    /**
     * @var Socket
     */
    protected $socketServer;

    /**
     * SocketServer constructor.
     * @param string $address
     */
    public function __construct($address)
    {
        $this->address = $address;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return SocketServer
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     *
     */
    public function run()
    {
        // Avoid zombie process child.
        pcntl_signal(SIGCHLD, SIG_IGN);

        // Create a daemon process.
        $pid = 0;//pcntl_fork();

        if ($pid == -1) {
            die('Fork unable.');
        } elseif ($pid) {
            echo 'Daemon started #'.$pid.PHP_EOL;
            $this->pid = $pid;
        } else {
            $this->pid = posix_getpid();
            $this->registerSigHandlers();

            cli_set_process_title('monitoring-server');
            $this->log('Daemon launch on: '.$this->address);

            // Create the main socket.
            $factory = new \Socket\Raw\Factory();
            $this->socketServer = $factory->createTcp4();

            // Help to reuse address.
            $this->socketServer->setOption(SOL_SOCKET, SO_REUSEADDR, 1);

            $this->socketServer->setBlocking(false);
            $this->socketServer->bind($this->address);
            $this->socketServer->listen($this->backLog);

            $loop = 0;

            while (true) {
                // Decrease CPU load.
                usleep(100000);

                // Maintain alive connection list up to date.
                if ($loop++ > 10) {
                    foreach ($this->connections as $pid) {
                        if (posix_getsid($pid) === false) {
                            unset($this->connections[$pid]);
                            $this->log('Connection process killed');
                            $this->log('Queue connections: '.count($this->connections));
                        }
                    }

                    $loop = 0;
                }

                if (count($this->connections) >= $this->maxClients) {
                    continue;
                }

                // Accept new connections.
                try {
                    $sock = $this->socketServer->accept();
                    $pid = pcntl_fork();

                    if ($pid == -1) {
                        $sock->shutdown();
                        $sock->close();

                        return;
                    } elseif ($pid) {
                        $this->connections[$pid] = $pid;
                    } else {
                        cli_set_process_title('monitoring-child');
                        $this->log('New connection from '.$sock->getPeerName());

                        $client = new SocketClient($this);
                        $client->handleConnection($sock, $this->timeout);

                        $sock->shutdown();
                        $sock->close();

                        return;
                    }
                } catch (\Exception $e) {

                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function registerSigHandlers()
    {
        pcntl_signal(SIGTERM, array($this, 'sigHandler'));
        pcntl_signal(SIGHUP, array($this, 'sigHandler'));
        pcntl_signal(SIGUSR1, array($this, 'sigHandler'));

        return true;
    }

    /**
     * @return bool
     */
    protected function killConnections()
    {
        // Only main process.
        if ($this->pid == posix_getpid()) {
            foreach ($this->connections as $pid) {
//                posix_kill($pid, SIGTERM);
                $this->log('Connection #'.$pid.' killed');
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
            }

            if ($this->socketServer) {
                $this->socketServer->shutdown();
                $this->socketServer->close();
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $signo
     */
    public function sigHandler($signo)
    {
        $this->log('Signal received (server): '.$signo);

        switch ($signo) {
            case SIGTERM:
                // gestion de l'extinction.
                $this->killConnections();
                $this->log('Process killed');
                exit;

            case SIGHUP:
                // gestion du redémarrage.
                $this->killConnections();
                $this->log('Restarted');
                break;

            case SIGUSR1:
                $this->log('Signal not supported');
                break;

            default:
                // gestion des autres signaux.
        }
    }

    /**
     * @param $message
     * @param int $level
     * @return bool
     */
    public function log($message, $level = LOG_INFO)
    {
        syslog($level, $message);

        return true;
    }
}
