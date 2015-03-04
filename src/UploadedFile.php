<?php

/**
 * @author Yan Li <peterleepersonal@gmail.com>
 */
class UploadedFile {
    /**
     * @var CUploadedFile
     */
    public $file;

    public function __construct($name) {
        $this->file = CUploadedFile::getInstanceByName($name);
    }

    /**
     * Verifies whether the file extension is in given list.
     * @param array $extensions The expected file extensions (without dot).
     * @return bool Returns whether the file extension can be found in the given list.
     */
    public function isExtensionInList($extensions) {
        $actual = $this->file->getExtensionName();
        foreach ($extensions as $expected) {
            if (0 === strcasecmp($actual, ltrim($expected, '.'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifies whether the file MIME type is in given list.
     * @param array $mimeTypes The expected file MIME types.
     * @return boolean Returns whether the file MIME type can be found in the given list.
     */
    public function isMimeTypeInList($mimeTypes) {
        $fileName = $this->file->getTempName();
        $actual = CFileHelper::getMimeType($fileName);
        if (empty($actual)) {
            $actual = $this->file->getType();
        }

        if (empty($actual)) {
            return false;
        }

        foreach ($mimeTypes as $expected) {
            if (0 === strcasecmp($expected, $actual)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifies whether the file is an image and its type is in given list.
     * @param array $imageTypes The expected image types.
     * @return boolean Returns whether the file is an image AND its type matches one of the types in the given list.
     */
    public function isImageTypeInList($imageTypes) {
        $fileName = $this->file->getTempName();
        $imageType = @exif_imagetype($fileName);
        if (false === $imageType) {
            return false;
        }

        return in_array($imageType, $imageTypes, true);
    }

    /**
     * Verifies whether the image dimensions are in range.
     * @param int $maxWidth Maximum allowed image width; pass a NULL if you don't want to limit the image width.
     * @param int $maxHeight Maximum allowed image height; pass a NULL if you don't want to limit the image height.
     * @param int $minWidth Minimum allowed image width; pass a NULL if you don't want to limit the image width.
     * @param int $minHeight Minimum allowed image height; pass a NULL if you don't want to limit the image height.
     * @return int Returns
     *  1 if the image exceeds maximum allowed width;
     *  2 if the image exceeds maximum allowed height;
     *  3 if the image exceeds minimum allowed width;
     *  4 if the image exceeds minimum allowed height.
     */
    public function validateImageDimensions($maxWidth, $maxHeight, $minWidth, $minHeight) {
        $fileName = $this->file->getTempName();
        $size = @getimagesize($fileName);

        if (!is_null($maxWidth) && $size[0] > $maxWidth) {
            return 1;
        }

        if (!is_null($maxHeight) > 0 && $size[1] > $maxHeight) {
            return 2;
        }

        if (!is_null($minWidth) && $size[0] < $minWidth) {
            return 3;
        }

        if (!is_null($minHeight) > 0 && $size[1] < $minHeight) {
            return 4;
        }

        return 0;
    }

    /**
     * Validates the uploaded file.
     * @param int $maxFileBytes Maximum allowed file size in bytes.
     * @param array $allowedExtensions Allowed file extensions.
     * @param array $allowedMimeTypes Allowed file MIME types.
     * @return string|null Returns the error message if validation fails, otherwise returns null.
     */
    public function validate($maxFileBytes, $allowedExtensions, $allowedMimeTypes) {
        if (is_null($this->file)) {
            return 'Please choose the file to upload.';
        }

        if ($maxFileBytes > 0 && $this->file->getSize() > $maxFileBytes) {
            $maxFileMegaBytes = floor($maxFileBytes / 1024 / 1024);
            return $this->file->getName() . " is too large! Please upload files up to {$maxFileMegaBytes}MB.";
        }

        if (is_array($allowedExtensions) && !$this->isExtensionInList($allowedExtensions)) {
            return 'Only files with [' . implode(', ', $allowedExtensions) . '] extensions are supported.';
        }

        if (is_array($allowedMimeTypes) && !$this->isMimeTypeInList($allowedMimeTypes)) {
            return 'Only files with type [' . implode(', ', $allowedMimeTypes) . '] are supported.';
        }

        return null;
    }

    /**
     * Validates the uploaded image file.
     * @param int $maxFileBytes Maximum allowed file size in bytes.
     * @param array $allowedExtensions Allowed file extensions.
     * @param array $allowedImageTypes Allowed image types.
     * @param int $maxWidth Maximum allowed image width; pass a NULL if you don't want to limit the image width.
     * @param int $maxHeight Maximum allowed image height; pass a NULL if you don't want to limit the image height.
     * @param int $minWidth Minimum allowed image width; pass a NULL if you don't want to limit the image width.
     * @param int $minHeight Minimum allowed image height; pass a NULL if you don't want to limit the image height.
     * @return string|null Returns the error message if validation fails, otherwise returns null.
     */
    public function validateImage($maxFileBytes, $allowedExtensions, $allowedImageTypes, $maxWidth = null, $maxHeight = null, $minWidth = null, $minHeight = null) {
        $error = $this->validate($maxFileBytes, $allowedExtensions, null);
        if (is_string($error)) {
            return $error;
        }

        if (is_array($allowedImageTypes) && !$this->isImageTypeInList($allowedImageTypes)) {
            return 'Unsupported image type.';
        }

        if ($maxWidth > 0 || $maxHeight > 0 || $minWidth > 0 || $minHeight > 0) {
            $dimensionResult = $this->validateImageDimensions($maxWidth, $maxHeight, $minWidth, $minHeight);
            if (1 === $dimensionResult) {
                return "Maximum image width is {$maxWidth}px.";
            }

            if (2 === $dimensionResult) {
                return "Maximum image height is {$maxHeight}px.";
            }

            if (3 === $dimensionResult) {
                return "Minimum image width is {$minWidth}px.";
            }

            if (4 === $dimensionResult) {
                return "Minimum image height is {$minHeight}px.";
            }
        }

        return null;
    }

    /**
     * Save the uploaded image file.
     * @param string $saveAs The file path used to save the uploaded file. NOTE: will try to create the directory if it doesn't exist.
     * @param bool $pngToJpg Whether try convert the PNG image to JPEG; NOTE: the original image will be saved if conversion failed.
     * @return string Returns the saved file path, it's might different from $saveAs argument.
     * @throws InvalidArgumentException If $saveAs is empty or file already exists.
     * @throws Exception If directory not found and unable to create it.
     */
    public function saveImage($saveAs, $pngToJpg = false) {
        if (empty($saveAs)) {
            throw new InvalidArgumentException('$saveAs cannot be empty.');
        }

        if (is_file($saveAs)) {
            throw new InvalidArgumentException('File already exists: ' . $saveAs);
        }

        $pathInfo = pathinfo($saveAs);
        $uploadDir = $pathInfo['dirname'];

        // creates the directory if not exists
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755)) {
            throw new Exception('Directory not found: ' . $uploadDir);
        }

        // try convert PNG to JPEG
        if ($pngToJpg && $this->isImageTypeInList(array(IMAGETYPE_PNG))) {
            $jpgFileName = $pathInfo['filename'] . '.jpg';
            $saveAs = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($jpgFileName, DIRECTORY_SEPARATOR);

            $image = imagecreatefrompng($this->file->getTempName());
            if (false !== $image && imagejpeg($image, $saveAs)) {
                imagedestroy($image);
                return $saveAs;
            }
        }

        if ($this->file->saveAs($saveAs)) {
            return $saveAs;
        }

        return null;
    }
}
