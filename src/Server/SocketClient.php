<?php

namespace Smalot\Minotor\Server;

use Socket\Raw\Socket;

/**
 * Class SocketClient
 * @package Smalot\Minotor\Server
 */
class SocketClient
{
    /**
     * @var SocketServer
     */
    protected $server;

    /**
     * @var Socket
     */
    protected $sock;

    /**
     * SocketClient constructor.
     * @param SocketServer $server
     */
    public function __construct(SocketServer $server)
    {
        $this->server = $server;

        $this->registerSigHandlers();
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->server = null;
    }

    /**
     * @param Socket $sock
     * @param int $timeout
     * @return bool
     */
    public function handleConnection(Socket $sock, $timeout = 300)
    {
        $this->sock = $sock;

        // Set non blocking socket to allow timeout detection.
        $sock->setBlocking(false);
        $sock->write('Welcome to "Monitoring Agent".'.PHP_EOL);

        $lastReception = microtime(true);
        $resource = $sock->getResource();

        while (true) {
            usleep(1000);

            $data = '';

            do {
                $buffer = '';
                $length = socket_recv($resource, $buffer, 1024, 0);
                $data .= $buffer;
            } while ($length > 0);

            $data = rtrim($data, "\r\n");

            if ($data) {
                $lastReception = microtime(true);
//                $this->log('Received: '.$data, LOG_DEBUG);
//                $this->log('Received: '.bin2hex($data), LOG_DEBUG);

                list($command,) = explode(' ', $data, 2);
                $commandHex = bin2hex($command);
                $command = strtolower($command);

                switch (true) {
                    case $command == 'exit':
                    case $command == 'quit':
                    case $commandHex == 'fff4fffd06': // CTRL+C
                    case $commandHex == '04':         // CTRL+D
                        $sock->write('200 Exit'."\r\n");
                        break(2);

                    default:
                        $this->log('Command:  "'.$command.'"');

                        if (method_exists($this, $command.'Command')) {
                            $this->{$command.'Command'}($sock, $data);

//                            if ($result) {
//                                socket_write($sock, '200 Success ' . $command . "\r\n");
//                            } else {
//                                socket_write($sock, '400 Error ' . $command . "\r\n");
//                            }
                        } else {
                            $sock->write('500 Unknown command ('.$command.')'."\r\n");
                        }
                }
            }

            if (($duration = microtime(true) - $lastReception) > $timeout) {
                $sock->write('200 Timeout after '.round($duration).' seconds'.PHP_EOL);
                $this->log('Connection closed by timeout: '.round($duration).' seconds');
                break;
            }
        }

        return true;
    }

    /**
     * @param $message
     * @param int $level
     * @return bool
     */
    protected function log($message, $level = LOG_INFO)
    {
        syslog($level, $message);

        return true;
    }

    /**
     * @return bool
     */
    protected function registerSigHandlers()
    {
        $this->log('Sig handler registered');

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
        if ($this->sock) {
            $this->sock->shutdown();
            $this->sock->close();
            $this->sock = null;
        }

        return false;
    }

    /**
     * @param int $signo
     */
    public function sigHandler($signo)
    {
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

    /** ---------------------------------------------------------------------- */

    public function helpCommand(Socket $sock, $data)
    {
        $sock->write('200 Commands: ping, help, put, exit'.PHP_EOL);
    }

    public function formatCommand(Socket $sock, $data)
    {
        $sock->write('200 Format changed'.PHP_EOL);
    }

    public function pingCommand(Socket $sock, $data)
    {
        $sock->write('200 Pong'.PHP_EOL);
    }

    public function putCommand(Socket $sock, $data)
    {
        $sock->write('200 Data stored'.PHP_EOL);
        $this->log($data);
    }
}
