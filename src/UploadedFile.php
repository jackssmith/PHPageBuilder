<?php

declare(strict_types=1);

namespace PHPageBuilder;

class UploadedFile
{
    /**
     * Public identifier for the uploaded file.
     */
    protected string $public_id;

    /**
     * Original file name.
     */
    protected string $original_file;

    /**
     * Create a new UploadedFile instance.
     */
    public function __construct(string $public_id, string $original_file)
    {
        $this->public_id     = trim($public_id);
        $this->original_file = trim($original_file);
    }

    /**
     * Return the URL of this uploaded file.
     */
    public function getUrl(): string
    {
        return phpb_full_url(
            rtrim(phpb_config('general.uploads_url'), '/') . '/' . $this->getRelativePath()
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
     * Convert the object to its URL representation.
     */
    public function __toString(): string
    {
        return $this->getUrl();
    }
}
