<?php
/**
 * Created by PhpStorm.
 * User: aleksandr
 * Date: 04.05.14
 * Time: 21:03
 */

namespace Socket;
use Socket\Exception\ConnectionException;

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
     */
    public function __construct($ip, $port, \Socket\Encryptor $encryptor = null, $type = self::SOCKET_CLIENT, $options = array()) {
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
            throw new \Socket\Exception\ConnectionException($this->toString() . "\n" . $errors);
        }
        $this->_postInit();
    }

    protected function _postInit() {}

    public function toString() {
        return __CLASS__ . "{ip:'$this->ip',port:'$this->port',encryptor:'$this->encryptor'}";
    }

    /**
     * @param \Socket\Encryptor $encryptor
     */
    public function setEncryptor(\Socket\Encryptor $encryptor) {
        $this->encryptor = $encryptor;
    }


    /**
     * @param string $data
     */
    public function write($data) {
        $this->_write($data);
    }

    final protected function _write($data) {
        if (null !== $this->encryptor) {
            $data = $this->encryptor->encrypt($data);
        }
        $result = @fputs($this->connection, $data);
        if (false === $result) {
            throw new \Socket\Exception\IOException($this->toString() . "\n" . $data);
        }
    }

    /**
     * @param int|null $size
     * @return string
     */
    public function read($size = null) {
        $data = $this->_read($size);
        return $data;
    }

    public function readLine($decrypt = false) {
        return $this->_readLine($decrypt);
    }

    final protected function _read($size = null) {
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

    final protected function _readLine($decrypt = false) {
        $data = @fgets($this->connection);
        if ($data !== false && null !== $this->encryptor && $decrypt) {
            $data = $this->encryptor->decrypt($data);
        }
        return $data;
    }


}