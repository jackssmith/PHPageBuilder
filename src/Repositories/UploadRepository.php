<?php

namespace PHPageBuilder\Repositories;

use PHPageBuilder\UploadedFile;
use InvalidArgumentException;

class UploadRepository extends BaseRepository
{
    /**
     * The uploads database table.
     *
     * @var string
     */
    protected $table = 'uploads';

    /**
     * The class that represents each uploaded file.
     *
     * @var string
     */
    protected $class = UploadedFile::class;

    /**
     * Create a new uploaded file.
     *
     * @param array $data
     * @return bool|object
     */
    public function create(array $data)
    {
        $requiredFields = ['public_id', 'original_file', 'mime_type', 'server_file'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                return false;
            }

            // Trim whitespace
            $data[$field] = trim($data[$field]);

            // Prevent empty strings
            if ($data[$field] === '') {
                return false;
            }
        }

        // Validate MIME type format
        if (!preg_match('/^[a-z0-9]+\/[a-z0-9\-\.\+]+$/i', $data['mime_type'])) {
            return false;
        }

        // Optional: limit filename length
        if (strlen($data['original_file']) > 255 || strlen($data['server_file']) > 255) {
            return false;
        }

        // Optional: ensure public_id is unique (if BaseRepository supports where())
        if ($this->where('public_id', $data['public_id'])->first()) {
            return false;
        }

        return parent::create([
            'public_id'     => $data['public_id'],
            'original_file' => $data['original_file'],
            'mime_type'     => $data['mime_type'],
            'server_file'   => $data['server_file'],
        ]);
    }
}
