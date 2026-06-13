<?php

namespace PHPageBuilder\Repositories;

use InvalidArgumentException;
use PHPageBuilder\UploadedFile;

class UploadRepository extends BaseRepository
{
    /**
     * Database table.
     */
    protected string $table = 'uploads';

    /**
     * Model class.
     */
    protected string $class = UploadedFile::class;

    /**
     * Required upload attributes.
     */
    private const REQUIRED_FIELDS = [
        'public_id',
        'original_file',
        'mime_type',
        'server_file',
    ];

    /**
     * Maximum filename length.
     */
    private const MAX_FILENAME_LENGTH = 255;

    /**
     * Store a new upload record.
     *
     * @param array $payload
     * @return object|bool
     */
    public function create(array $payload)
    {
        try {
            $uploadData = $this->sanitizeAndValidate($payload);

            if ($this->uploadExists($uploadData['public_id'])) {
                return false;
            }

            return parent::create($uploadData);

        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Validate and normalize upload data.
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeAndValidate(array $data): array
    {
        $clean = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            $clean[$field] = $this->validateStringField(
                $data[$field] ?? null,
                $field
            );
        }

        $this->validateMimeType($clean['mime_type']);

        $this->validateFilenameLength($clean['original_file']);
        $this->validateFilenameLength($clean['server_file']);

        return $clean;
    }

    /**
     * Validate string fields.
     */
    protected function validateStringField($value, string $field): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                sprintf('Field "%s" must be a string.', $field)
            );
        }

        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException(
                sprintf('Field "%s" cannot be empty.', $field)
            );
        }

        return $value;
    }

    /**
     * Validate MIME type.
     */
    protected function validateMimeType(string $mimeType): void
    {
        $pattern = '/^[a-z0-9]+\/[a-z0-9.+-]+$/i';

        if (!preg_match($pattern, $mimeType)) {
            throw new InvalidArgumentException(
                sprintf('Invalid MIME type: %s', $mimeType)
            );
        }
    }

    /**
     * Validate filename length.
     */
    protected function validateFilenameLength(string $filename): void
    {
        if (mb_strlen($filename) > self::MAX_FILENAME_LENGTH) {
            throw new InvalidArgumentException(
                'Filename exceeds allowed length.'
            );
        }
    }

    /**
     * Check if upload already exists.
     */
    protected function uploadExists(string $publicId): bool
    {
        return (bool) $this->where('public_id', $publicId)->first();
    }
}
