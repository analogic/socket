<?php
/**
 * Original by: aleksandr 04.05.14 21:03
 * Updated by: sh@analogic.cz
 */

namespace Socket;
use Socket\Exception\ConnectionException;
use Socket\Exception\IOException;

/**
 * Class Socket
 * @package Socket
 */
class Socket {
    const TIMEOUT = 5;
    const SOCKET_SERVER = "server";
    const SOCKET_CLIENT = "client";
    /**
     * @var string
     */
    protected $ip;
    /**
     * @var int|string
     */
    protected $port;
    /**
     * @var \Socket\Encryptor
     */
    protected $encryptor;

    /**
     * @var resource
     */
    protected $connection;

    protected $options;

    /**
     * @param $ip
     * @param $port
     * @param Encryptor $encryptor
     * @param $type
     * @param $options
     * @throws \Exception
     */
    public function __construct($ip, $port, Encryptor $encryptor = null, $type = self::SOCKET_CLIENT, $options = array()) {
        $this->ip = $ip;
        $this->port = $port;
        $this->encryptor = $encryptor;
        $this->options = $options;

        if (self::SOCKET_CLIENT === $type) {
            $this->initConnection();
        } elseif (self::SOCKET_SERVER === $type) {
            $this->initSocket();
        } else {
            throw new \Exception("Socket type '$type' undefined");
        }
    }

    public function __toString() {
        return __CLASS__ . " {ip:'$this->ip',port:'$this->port'}";
    }

    protected function initSocket() {
        $domain = array_key_exists("domain", $this->options) ? $this->options['domain'] : AF_INET;
        $type = array_key_exists("type", $this->options) ? $this->options['type'] : SOCK_STREAM;
        $protocol = array_key_exists("protocol", $this->options) ? $this->options['protocol'] : SOL_UDP;

        $socket = socket_create($domain, $type, $protocol);

        if (false === $socket)
            throw new ConnectionException("Can't create a socket");
        if (!socket_bind($socket, $this->ip, $this->port))
            throw new ConnectionException("Can't bind a socket");

        $this->connection = $socket;
    }

    protected function initConnection() {
        $this->connection = @fsockopen(
            $this->ip,
            $this->port,
            $err,
            $errorMessage,
            self::TIMEOUT
        );
        if (!$this->connection) {
            $errors = 'Socket connection not opened. '.$errorMessage."\n";
            throw new ConnectionException($this->__toString() . " " . $errors);
        }
    }

    /**
     * @param Encryptor $encryptor
     */
    public function setEncryptor(Encryptor $encryptor) {
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $data
     * @throws IOException
     */
    public function write($data) {
        if (null !== $this->encryptor) {
            $data = $this->encryptor->encrypt($data);
        }
        $result = @fputs($this->connection, $data);
        if (false === $result) {
            throw new IOException($this->__toString() . " " . $data);
        }
    }

    /**
     * @param int|null $size
     * @return string
     */
    public function read($size = null) {
        $data = "";
        $blockSize = 256;
        if (null !== $size) {
            while ($size > 0) {
                $data .= @fread($this->connection, $blockSize < $size ? $blockSize : $size);
                $size -= $blockSize;
            }
        } else {
            $data = @fread($this->connection, $size);
        }
        if ($data !== false && null !== $this->encryptor) {
            $data = $this->encryptor->decrypt($data);
        }
        return $data;
    }

    public function readLine($decrypt = false) {
        $data = @fgets($this->connection);
        if ($data !== false && null !== $this->encryptor && $decrypt) {
            $data = $this->encryptor->decrypt($data);
        }
        return $data;
    }

    public function eof() {
        return feof($this->connection);
    }
}