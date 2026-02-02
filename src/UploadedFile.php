<?php

declare(strict_types=1);

namespace PHPageBuilder;

use InvalidArgumentException;

class UploadedFile
{
    /**
     * Public identifier for the uploaded file (e.g. UUID or hash).
     */
    protected readonly string $public_id;

    /**
     * Original file name as uploaded by the user.
     */
    protected readonly string $original_file;

    /**
     * Create a new UploadedFile instance.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $public_id, string $original_file)
    {
        $public_id     = trim($public_id);
        $original_file = trim($original_file);

        if ($public_id === '') {
            throw new InvalidArgumentException('Public ID cannot be empty.');
        }

        if ($original_file === '') {
            throw new InvalidArgumentException('Original file name cannot be empty.');
        }

        // Prevent directory traversal via file name
        $original_file = basename($original_file);

        $this->public_id     = $public_id;
        $this->original_file = $original_file;
    }

    /**
     * Return the absolute URL of this uploaded file.
     */
    public function getUrl(): string
    {
        $baseUrl = rtrim((string) phpb_config('general.uploads_url'), '/');

        return phpb_full_url(
            $baseUrl . '/' . $this->getRelativePath()
        );
    }

    /**
     * Get the relative path of the file.
     */
    public function getRelativePath(): string
    {
        return $this->public_id . '/' . $this->original_file;
    }

    /**
     * Get the public ID.
     */
    public function getPublicId(): string
    {
        return $this->public_id;
    }

    /**
     * Get the original file name.
     */
    public function getOriginalFile(): string
    {
        return $this->original_file;
    }

    /**
     * Get the file extension (lowercased, without dot).
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->original_file, PATHINFO_EXTENSION));
    }

    /**
     * Get the file name without extension.
     */
    public function getBasename(): string
    {
        return pathinfo($this->original_file, PATHINFO_FILENAME);
    }

    /**
     * Export the uploaded file as an array.
     */
    public function toArray(): array
    {
        return [
            'public_id'      => $this->public_id,
            'original_file'  => $this->original_file,
            'relative_path'  => $this->getRelativePath(),
            'url'            => $this->getUrl(),
        ];
    }

    /**
     * Convert the object to its URL representation.
     */
    public function __toString(): string
    {
        return $this->getUrl();
    }
}
