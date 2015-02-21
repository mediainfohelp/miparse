# miparse

mediainfo log parser

handles any number of logs within the input text

generates HTML table(s) with the original log hidden behind a javascript toggle

![](http://i.imgur.com/0HENcQw.jpg)

## Usage

```php
$mediainfo = new miparse;
$mediainfo->parse($text);
echo $mediainfo->output;
```

Data for each parsed log is accessible from the object:
```php
print_r($mediainfo->logs);
```

### Notes

with thanks to PTP, ptpuploader and Owncloud
