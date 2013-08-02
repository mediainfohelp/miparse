<?
include "include/mediainfo.php";

$test = "
text outside the log block won't be affected.

click the filename to see the full log:

General
Complete name                            : c:\path\will\be\hidden\SOCPA - The Movie.avi
Format                                   : AVI <script>alert('xss test');</script>
Format/Info                              : Audio Video Interleave
File size                                : 700 MiB
Duration                                 : 1h 0mn
Overall bit rate                         : 1 612 Kbps
Writing application                      : VirtualDubMod 1.5.4.1 (build 2178/release)
Writing library                          : VirtualDubMod build 2178/release

Video
ID                                       : 0
Format                                   : MPEG-4 Visual
Format profile                           : Advanced Simple@L5
Format settings, BVOP                    : 1
Format settings, QPel                    : No
Format settings, GMC                     : No warppoints
Format settings, Matrix                  : Custom
Codec ID                                 : XVID
Codec ID/Hint                            : XviD
Duration                                 : 1h 0mn
Bit rate                                 : 1 413 Kbps
Width                                    : 512 pixels
Height                                   : 384 pixels
Display aspect ratio                     : 4:3
Frame rate                               : 25.000 fps
Color space                              : YUV
Chroma subsampling                       : 4:2:0
Bit depth                                : 8 bits
Scan type                                : Progressive
Compression mode                         : Lossy
Bits/(Pixel*Frame)                       : 0.287
Stream size                              : 613 MiB (88%)
Writing library                          : XviD 1.3.0.dev55

Audio
ID                                       : 1
Format                                   : MPEG Audio
Format version                           : Version 1
Format profile                           : Layer 3
Mode                                     : Joint stereo
Mode extension                           : MS Stereo
Codec ID                                 : 55
Codec ID/Hint                            : MP3
Duration                                 : 1h 0mn
Bit rate mode                            : Constant
Bit rate                                 : 192 Kbps
Channel(s)                               : 2 channels
Sampling rate                            : 48.0 KHz
Compression mode                         : Lossy
Stream size                              : 83.4 MiB (12%)
Alignment                                : Aligned on interleaves
Interleave, duration                     : 80 ms (2.00 video frames)
Interleave, preload duration             : 504 ms
Writing library                          : LAME3.98r
Encoding settings                        : -m j -V 4 -q 2 -lowpass 18.6 -b 192

text outside the log block won't be affected.

<script>alert('xss test');</script>

";

?>

<html>
<head>
<link rel="stylesheet" href="include/style.css" type="text/css" media="all">
<script type="text/javascript" src="include/script.js" language="javascript"></script>
</head>
<body>

<? echo miparse::parse($test); ?>

</body>
</html>