<?php
/**
 * Created by PhpStorm.
 * User: aleksandr
 * Date: 04.05.14
 * Time: 21:03
 */

namespace Socket;

/**
 * Class Socket
 * @package Socket
 */
class Socket {
    const TIMEOUT = 5;
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

    /**
     * @param $ip
     * @param $port
     * @param Encryptor $encryptor
     */
    public function __construct($ip, $port, \Socket\Encryptor $encryptor = null) {
        $this->ip = $ip;
        $this->port = $port;
        $this->encryptor = $encryptor;
        $this->initConnection();
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
        $data = @fread($this->connection, $size);
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