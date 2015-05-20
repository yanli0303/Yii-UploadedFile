# Yii-UploadedFile #
*By [Yan Li](https://github.com/yanli0303)* 

<!--
[![Latest Stable Version](http://img.shields.io/packagist/v/yanli0303/yii-uploaded-file.svg)](https://packagist.org/packages/yanli0303/yii-uploaded-file)
[![Total Downloads](https://img.shields.io/packagist/dt/yanli0303/yii-uploaded-file.svg)](https://packagist.org/packages/yanli0303/yii-uploaded-file)
-->
[![Build Status](https://travis-ci.org/yanli0303/Yii-UploadedFile.svg?branch=master)](https://travis-ci.org/yanli0303/Yii-UploadedFile)
[![Coverage Status](https://coveralls.io/repos/yanli0303/Yii-UploadedFile/badge.svg?branch=master)](https://coveralls.io/r/yanli0303/Yii-UploadedFile?branch=master)
[![License](https://img.shields.io/badge/License-MIT-brightgreen.svg)](https://packagist.org/packages/yanli0303/yii-uploaded-file)
[![PayPayl donate button](http://img.shields.io/badge/paypal-donate-orange.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=silentwait4u%40gmail%2ecom&lc=US&item_name=Yan%20Li&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3apaypal%2ddonate%2ejpg%3aNonHostedGuest)

A wrapper for CUploadedFile class of PHP Yii framework.
It adds following help methods to CUploadedFile class:

- isExtensionInList($extensions)
- isMimeTypeInList($mimeTypes)
- isImageTypeInList($imageTypes)
- validateImageDimensions($maxWidth, $maxHeight, $minWidth, $minHeight)
- validate($maxFileBytes, $allowedExtensions, $allowedMimeTypes)
- validateImage($maxFileBytes, $allowedExtensions, $allowedImageTypes, $maxWidth = null, $maxHeight = null, $minWidth = null, $minHeight = null)
- saveImage($saveAs, $pngToJpg = false)

## Usage ##

```PHP
$maxFileBytes = 4194304; //4 * 1024 * 1024 = 4MB
$allowedImageFileExtensions = array('.png', '.jpg', '.jpeg');
$allowedImageTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG);

$uploaded = new UploadedFile('file');
$error = $uploaded->validateImage($maxFileBytes, $allowedImageFileExtensions, $allowedImageTypes);
if (is_string($error)) {
    throw new Exception($error);
}

$saveAs = $uploaded->saveImage('/webroot/uploads/images/'.basename($uploaded->file->getName()), false);
if (empty($saveAs)) {
    throw new Exception('An error has occurred and we couldn\'t upload the image. Please try again later.');
}

// do sth with saved image: $saveAs
```
