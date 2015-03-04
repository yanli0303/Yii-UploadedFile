<?php

require_once(__DIR__ . '/../../src/UploadedFile.php');

/**
 * @author Yan Li <peterleepersonal@gmail.com>
 */
class UploadedFileTest extends CTestCase {

    private function getFileInstance($ext) {
        $fileName = 'file.' . $ext;
        $filePath = implode(DIRECTORY_SEPARATOR, array(
            __DIR__,
            'files',
            $ext . '.' . $ext
        ));
        $mimeTypes = array(
            'txt' => 'text/plain',
            'png' => 'image/png',
            'jpg' => 'image/jpg',
        );

        $file = new UploadedFile(null);
        $file->file = new CUploadedFile($fileName, $filePath, $mimeTypes[$ext], 12345, UPLOAD_ERR_OK);
        return $file;
    }

    public function testIsExtensionInList() {
        $file = $this->getFileInstance('jpg');

        $this->assertTrue($file->isExtensionInList(array('jpg')));
        $this->assertTrue($file->isExtensionInList(array('test', '.jPG')));

        $this->assertFalse($file->isExtensionInList(array()));
        $this->assertFalse($file->isExtensionInList(array('')));
        $this->assertFalse($file->isExtensionInList(array('tmp', 'ext')));
    }

    public function testIsMimeTypeInList() {
        $file = $this->getFileInstance('jpg');

        $this->assertTrue($file->isMimeTypeInList(array('image/jpeg')));
        $this->assertTrue($file->isMimeTypeInList(array('text/plain', 'image/jpEG')));

        $this->assertFalse($file->isMimeTypeInList(array()));
        $this->assertFalse($file->isMimeTypeInList(array('')));
        $this->assertFalse($file->isMimeTypeInList(array('text/plain', 'image/png')));
    }

    public function testIsImageTypeInList() {
        $file = $this->getFileInstance('png');

        $this->assertTrue($file->isImageTypeInList(array(IMAGETYPE_PNG)));
        $this->assertTrue($file->isImageTypeInList(array(IMAGETYPE_JPEG, IMAGETYPE_PNG)));

        $this->assertFalse($file->isImageTypeInList(array()));
        $this->assertFalse($file->isImageTypeInList(array('')));
        $this->assertFalse($file->isImageTypeInList(array(IMAGETYPE_JPEG)));
    }

    public function testValidateImageDimensions() {
        // 320x480
        $file = $this->getFileInstance('jpg');

        $this->assertEquals(0, $file->validateImageDimensions(null, null, null, null));
        $this->assertEquals(0, $file->validateImageDimensions(321, 481, 320, 480));

        $this->assertEquals(1, $file->validateImageDimensions(319, 481, 320, 480));
        $this->assertEquals(2, $file->validateImageDimensions(321, 479, 320, 480));

        $this->assertEquals(3, $file->validateImageDimensions(321, 481, 321, 480));
        $this->assertEquals(4, $file->validateImageDimensions(321, 481, 320, 481));
    }

    public function validateDataProvider() {
        $data = array();

        $txtFile = $this->getFileInstance('txt');
        $pngFile = $this->getFileInstance('png');

        // no file
        $data[] = array(new UploadedFile(null), null, null, null, 'Please choose the file to upload.');

        // file too large
        $data[] = array($txtFile, 1, null, null, $txtFile->file->getName() . " is too large! Please upload files up to 0MB.");

        // extension not allowed
        $data[] = array($txtFile, null, array('jpg', 'png'), null, 'Only files with [jpg, png] extensions are supported.');

        // invalid mime type
        $data[] = array($pngFile, null, null, array('text/plain', 'image/jpeg'), 'Only files with type [text/plain, image/jpeg] are supported.');

        // valid
        $data[] = array($pngFile, 1 * 1024 * 1024, array('jpg', 'png'), array('text/plain', 'image/png'), null);

        return $data;
    }

    /**
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(UploadedFile $file, $maxFileBytes, $allowedExtensions, $allowedMimeTypes, $expected) {
        $actual = $file->validate($maxFileBytes, $allowedExtensions, $allowedMimeTypes);
        if (is_null($expected)) {
            $this->assertNull($actual);
        } else {
            $this->assertEquals($expected, $actual);
        }
    }

    public function validateImageDataProvider() {
        $data = array();

        $txtFile = $this->getFileInstance('txt');
        $jpgFile = $this->getFileInstance('jpg');

        // file too large
        $data[] = array($jpgFile, 1, null, null, null, null, null, null, $jpgFile->file->getName() . " is too large! Please upload files up to 0MB.");

        // extension not allowed
        $data[] = array($jpgFile, null, array('png', 'txt'), null, null, null, null, null, 'Only files with [png, txt] extensions are supported.');

        // invalid image type
        $data[] = array($txtFile, null, null, array(IMAGETYPE_JPEG, IMAGETYPE_PNG), null, null, null, null, 'Unsupported image type.');
        $data[] = array($jpgFile, null, null, array(IMAGETYPE_PNG, IMAGETYPE_GIF), null, null, null, null, 'Unsupported image type.');

        // invalid dimensions
        $data[] = array($jpgFile, null, null, null, 319, null, null, null, 'Maximum image width is 319px.');
        $data[] = array($jpgFile, null, null, null, null, 479, null, null, 'Maximum image height is 479px.');
        $data[] = array($jpgFile, null, null, null, null, null, 321, null, 'Minimum image width is 321px.');
        $data[] = array($jpgFile, null, null, null, null, null, null, 481, 'Minimum image height is 481px.');

        // valid
        $data[] = array($jpgFile, 1 * 1024 * 1024, array('png', 'JPG'), array(IMAGETYPE_PNG, IMAGETYPE_JPEG), 320, 480, 320, 480, null);

        return $data;
    }

    /**
     *
     * @dataProvider validateImageDataProvider
     */
    public function testValidateImage(UploadedFile $file, $maxFileBytes, $allowedExtensions, $allowedImageTypes, $maxWidth, $maxHeight, $minWidth, $minHeight, $expected) {
        $actual = $file->validateImage($maxFileBytes, $allowedExtensions, $allowedImageTypes, $maxWidth, $maxHeight, $minWidth, $minHeight);
        if (is_null($expected)) {
            $this->assertNull($actual);
        } else {
            $this->assertEquals($expected, $actual);
        }
    }

    public function saveImageInvalidArgumentExceptionDataProvider() {
        $data = array();

        $pngFile = $this->getFileInstance('png');

        // empty file
        $data[] = array($pngFile, '', false);
        $data[] = array($pngFile, null, false);

        // file exists
        $data[] = array($pngFile, __FILE__, false);

        return $data;
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider saveImageInvalidArgumentExceptionDataProvider
     */
    public function testSaveImageExpectsInvalidArgumentException(UploadedFile $file, $saveAs, $pngToJpg) {
        $file->saveImage($saveAs, $pngToJpg);
    }

    /**
     * @expectedException Exception
     */
    public function testSaveImageMkdirFailed() {
        uopz_function('mkdir', function() {
            return false;
        });

        $pngFile = $this->getFileInstance('png');
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'not_exist_dir';
        if (is_dir($dir)) {
            rmdir($dir);
        }

        if (is_dir($dir)) {
            $this->fail('Unable to remove temporary directory: ' . $dir);
        }

        $saveAs = $dir . DIRECTORY_SEPARATOR . uniqid('png', true) . '.tmp';
        $pngFile->saveImage($saveAs, false);
    }

    public function testSaveImage() {
        $pngFile = $this->getFileInstance('png');
        $jpgFile = $this->getFileInstance('jpg');

        // not convert
        uopz_function('CUploadedFile', 'saveAs', function($saveAs) use (&$pngFile) {
            return copy($pngFile->file->getTempName(), $saveAs);
        });
        $saveAs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('png', true) . '.tmp';
        $actual = $pngFile->saveImage($saveAs, false);
        $this->assertEquals($saveAs, $actual);
        $this->assertFileExists($saveAs);

        // convert, but jpg
        uopz_function('CUploadedFile', 'saveAs', function($saveAs) use (&$jpgFile) {
            return copy($jpgFile->file->getTempName(), $saveAs);
        });
        $saveAs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('jpg', true) . '.tmp';
        $actual = $jpgFile->saveImage($saveAs, true);
        $this->assertEquals($saveAs, $actual);
        $this->assertFileExists($saveAs);

        // convert
        $saveAs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('jpg', true) . '.tmp';
        $actual = $pngFile->saveImage($saveAs, true);
        $this->assertNotEquals($saveAs, $actual);
        $this->assertStringEndsWith('.jpg', $actual);
        $this->assertFileExists($actual);

        // save failed
        uopz_function('CUploadedFile', 'saveAs', function($saveAs) {
            return false;
        });
        $saveAs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('png', true) . '.tmp';
        $actual = $pngFile->saveImage($saveAs, false);
        $this->assertNull($actual);
    }
}
