<?php

namespace Service;

use Symfony\Component\Filesystem\Filesystem;

class Config {

    private $file;
    private $config;
    private $fs;

    public function __construct(string $file) {
        $this->fs = new Filesystem();
        if($this->fs->exists($file)) {
            $this->file = $file;
            $this->config = json_decode(file_get_contents($file),true);
        } else {
            $this->config = [
                'folders' => [
                    'src' => '',
                    'out' => ''
                ]
            ];
            $this->save();
        }
    }

    public function setSrc(string $path) {
        $this->config['folders']['src'] = htmlspecialchars($path);
    }

    public function setOut(string $path) {
        $this->config['folders']['out'] = htmlspecialchars($path);
    }

    /**
     * Get config or null
     * @return array|null
     */
    public function get(): ?array {
        return $this->config;
    }

    public function save(): bool {
        if(file_put_contents($this->file, json_encode($this->config))) {
            return true;
        }
        return false;
    }

}