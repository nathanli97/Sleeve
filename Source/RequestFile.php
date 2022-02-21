<?php

namespace Sleeve;

/**
 * The wrapper class for uploaded files.
 * @see https://www.php.net/manual/en/features.file-upload.php
 */
class RequestFile
{
    public string $name;
    public string $type;
    public int $size;
    public string $tmp_name;
    public int $error;
    public ?string $full_path;

    /**
     * @param string      $name      The uploaded file name
     * @param string      $type      The mime type of the file, if the browser provided this information.
     * @param int         $size      The size, in bytes, of the uploaded file
     * @param string      $tmp_name  The temporary filename of the uploaded file
     * @param int         $error     The error code associated with this file upload.
     * @param string|null $full_path The full path as submitted by the browser. Available as of PHP 8.1.0.
     * @see https://www.php.net/manual/en/features.file-upload.post-method.php
     * @after PHP 8.1.0
     */
    public function __construct(
        string $name,
        string $type,
        int $size,
        string $tmp_name,
        int $error,
        ?string $full_path
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->tmp_name = $tmp_name;
        $this->error = $error;
        $this->full_path = $full_path;
    }
}
