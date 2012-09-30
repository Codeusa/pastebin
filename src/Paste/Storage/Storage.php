<?php

namespace Paste\Storage;

use Paste\Entity\Paste;
use Paste\Math\Base62;

class Storage
{
    private $db;

    public function __construct(\Doctrine\DBAL\Connection $db)
    {
        $this->db = $db;
    }

    public function get($id)
    {
        $sql = 'SELECT p.id, c.content, p.filename, p.token, p.timestamp, p.ip '
             . 'FROM pastes p, paste_content c '
             . 'WHERE p.content_id = c.id AND p.token = :token';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':token', $id);
        $stmt->execute();
        $result = $stmt->fetch();

        // The statement failed to execute.
        if (false === $stmt->execute()) {
            throw new \RuntimeException('SQL statement failed to execute.');
        }

        // There are no results.
        if (false === $result = $stmt->fetch()) {
            return false;
        }

        // Assemble a paste model.
        $paste = new Paste();
        $paste->setId($result['id']);
        $paste->setContent($result['content']);
        $paste->setTimestamp($result['timestamp']);
        $paste->setToken($result['token']);
        $paste->setFilename($result['filename']);
        $paste->setBinaryIp($result['ip']);

        return $paste;
    }

    public function save($paste)
    {
        $base62 = new Base62;

        if (null != $filename = $paste->getFilename()) {
            $filename = $paste->getFilename();
        }

        $contentId = $this->alreadyExists($paste->getDigest());

        if ($contentId === false) {
            $sql = 'INSERT INTO paste_content (content, digest) '
                 . 'VALUES (:content, :digest)';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':content', $paste->getContent());
            $stmt->bindValue(':digest', $paste->getDigest()); 
            $stmt->execute();
            $contentId = $this->db->lastInsertId();
        }

        $sql = 'INSERT INTO pastes (filename, ip, content_id) '
             . 'VALUES (:filename, :ip, :content_id)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':filename', $filename);
        $stmt->bindValue(':ip', $paste->getBinaryIp());
        $stmt->bindValue(':content_id', $contentId);
        $stmt->execute();
        $id = $this->db->lastInsertId();

        $stmt = null;

        $token = $base62->encode($id);

        $sql = 'UPDATE pastes '
             . 'SET token = :token WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':token', $token);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $token;
    }

    /*
    public function getId($int)
    {
        $alphabet = self::DIGITS . self::ASCII_LOWERCASE . self::ASCII_UPPERCASE;

        if ($int === 0) {
            return $alphabet[0];
        }

        $stack = array();
        
        while($int) {
            $remainder = $int % 62;
            $int = floor($int / 62);
            $stack[] = $alphabet[$remainder];
        }

        $stack = array_reverse($stack);

        return implode($stack);
    }
    */

    public function alreadyExists($digest)
    {
        $sql = 'SELECT id FROM paste_content WHERE digest = :digest';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':digest', $digest);
        $stmt->execute();

        $contentId = $stmt->fetch();

        if ($contentId !== false) {
            $contentId = (int) $contentId;
        }

        return $contentId;
    }

    public function close()
    {
        $this->db = null;
    }

    public function __destruct()
    {
        $this->close();
    }
}
