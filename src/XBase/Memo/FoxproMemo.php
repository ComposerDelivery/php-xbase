<?php

namespace XBase\Memo;

class FoxproMemo extends AbstractMemo
{
    const BLOCK_LENGTH_LENGTH = 4;

    const BLOCK_TYPE_LENGTH = 4;

    /** @var int */
    protected $nextFreeBlock;

    /** @var int */
    protected $blockSize;

    protected function readHeader(): void
    {
        $this->nextFreeBlock = unpack('N', $this->fp->read(4))[1];
        $this->fp->seek(6);
        $this->blockSize = unpack('n', $this->fp->read(2))[1];

        if (filesize($this->filepath) !== $this->nextFreeBlock * $this->blockSize) {
            @trigger_error('Incorrect next_available_block pointer', E_USER_WARNING);
        }
    }

    public static function getExtension(): string
    {
        return 'fpt';
    }

    /**
     * @param int $pointer Block address.
     */
    public function get(int $pointer): ?MemoObject
    {
        if (!$this->isOpen()) {
            $this->open();
        }

        if (0 === $pointer) {
            return null;
        }

        $this->fp->seek($pointer * $this->blockSize);
        $type = unpack('N', $this->fp->read(self::BLOCK_TYPE_LENGTH)); //todo figure out type-enums

        $memoLength = unpack('N', $this->fp->read(self::BLOCK_LENGTH_LENGTH));
        $result = $this->fp->read($memoLength[1]);

        $type = $this->guessDataType($result);
        if ($this->convertFrom) {
            $result = iconv($this->convertFrom, 'utf-8', $result);
        }

        return new MemoObject($result, $type, $pointer, $memoLength[1]);
    }
}
