<?

$text = <<<EOF
text outside the log block won't be affected.
click the filename to see the full log:
General
Complete name : c:\path\will\be\hidden\SOCPA - The Movie.avi
Format : AVI
Format/Info : Audio Video Interleave
File size : 700 MiB
Duration : 1h 0mn
Overall bit rate : 1 612 Kbps
Writing application : VirtualDubMod 1.5.4.1 (build 2178/release)
Writing library : VirtualDubMod build 2178/release

Video
ID : 0
Format : MPEG-4 Visual
Format profile : Advanced Simple@L5
Format settings, BVOP : 1
Format settings, QPel : No
Format settings, GMC : No warppoints
Format settings, Matrix : Custom
Codec ID : XVID
Codec ID/Hint : XviD
Duration : 1h 0mn
Bit rate : 1 413 Kbps
Width : 512 pixels
Height : 384 pixels
Display aspect ratio : 4:3
Frame rate : 25.000 fps
Color space : YUV
Chroma subsampling : 4:2:0
Bit depth : 8 bits
Scan type : Progressive
Compression mode : Lossy
Bits/(Pixel*Frame) : 0.287
Stream size : 613 MiB (88%)
Writing library : XviD 1.3.0.dev55

Audio 
ID : 1
Language : English
Format : MPEG Audio
Format version : Version 1
Format profile : Layer 3
Mode : Joint stereo
Mode extension : MS Stereo
Codec ID : 55
Codec ID/Hint : MP3
Duration : 1h 0mn
Bit rate mode : Constant
Bit rate : 192 Kbps
Channel(s) : 2 channels
Sampling rate : 48.0 KHz
Compression mode : Lossy
Stream size : 83.4 MiB (12%)
Alignment : Aligned on interleaves
Interleave, duration : 80 ms (2.00 video frames)
Interleave, preload duration : 504 ms
Writing library : LAME3.98r
Encoding settings : -m j -V 4 -q 2 -lowpass 18.6 -b 192

Audio #2
ID : 2
Language : English
Title : commentary
Format : MPEG Audio
Format version : Version 1
Format profile : Layer 3
Mode : Joint stereo
Mode extension : MS Stereo
Codec ID : 55
Codec ID/Hint : MP3
Duration : 1h 0mn
Bit rate mode : Constant
Bit rate : 192 Kbps
Channel(s) : 2 channels
Sampling rate : 48.0 KHz
Compression mode : Lossy
Stream size : 83.4 MiB (12%)
Alignment : Aligned on interleaves
Interleave, duration : 80 ms (2.00 video frames)
Interleave, preload duration : 504 ms
Writing library : LAME3.98r
Encoding settings : -m j -V 4 -q 2 -lowpass 18.6 -b 192

text outside the log block won't be affected.
<script>alert('xss test');</script>

Multiple logs are supported:
General
Unique ID : 208761194743062182799981888428456161223 (0x9D0DF190F5E413C6F66B1F47580E2FC7)
Complete name : The.Internets.Own.Boy.Aaron.Swartz.mkv
Format : Matroska
Format version : Version 4 / Version 2
File size : 1.46 GiB
Duration : 1h 44mn
Overall bit rate : 1 995 Kbps
Encoded date : UTC 2014-06-27 19:16:56
Writing application : mkvmerge v6.9.1 ('Blue Panther') 64bit built on Jun 27 2014 14:01:28
Writing library : libebml v1.3.0 + libmatroska v1.4.1

Video
ID : 1
Format : AVC
Format/Info : Advanced Video Codec
Format profile : High@L3.1
Format settings, CABAC : Yes
Format settings, ReFrames : 4 frames
Codec ID : V_MPEG4/ISO/AVC
Duration : 1h 44mn
Width : 1 280 pixels
Height : 720 pixels
Display aspect ratio : 16:9
Frame rate mode : Constant
Frame rate : 23.976 fps
Color space : YUV
Chroma subsampling : 4:2:0
Bit depth : 8 bits
Scan type : Progressive
Writing library : x264 core 142 r2431 a5831aa
Encoding settings : cabac=1 / ref=3 / deblock=1:0:0 / analyse=0x3:0x113 / me=hex / subme=7 / psy=1 / psy_rd=1.00:0.00 / mixed_ref=1 / me_range=16 / chroma_me=1 / trellis=1 / 8x8dct=1 / cqm=0 / deadzone=21,11 / fast_pskip=1 / chroma_qp_offset=-2 / threads=24 / lookahead_threads=4 / sliced_threads=0 / nr=0 / decimate=1 / interlaced=0 / bluray_compat=0 / stitchable=1 / constrained_intra=0 / bframes=3 / b_pyramid=2 / b_adapt=1 / b_bias=0 / direct=1 / weightb=1 / open_gop=0 / weightp=2 / keyint=72 / keyint_min=24 / scenecut=40 / intra_refresh=0 / rc_lookahead=40 / rc=crf / mbtree=1 / crf=20.0 / qcomp=0.60 / qpmin=5 / qpmax=69 / qpstep=4 / vbv_maxrate=2750 / vbv_bufsize=7500 / crf_max=0.0 / nal_hrd=none / filler=0 / ip_ratio=1.40 / aq=1:1.00
Default : Yes
Forced : No
Color primaries : BT.709
Transfer characteristics : BT.709
Matrix coefficients : BT.709

Audio
ID : 2
Title : <script>alert('xss test');</script>
Format : AAC
Format/Info : Advanced Audio Codec
Format profile : LC
Codec ID : A_AAC
Duration : 1h 44mn
Channel(s) : 2 channels
Channel positions : Front: L R
Sampling rate : 48.0 KHz
Compression mode : Lossy
Language : English
Default : Yes
Forced : No

EOF;

require_once "include/mediainfo.php";
$mediainfo = new miparse;
$mediainfo->parse($text);

?>

<html>
<head>
<link rel="stylesheet" href="include/style.css" type="text/css" media="all">
<script type="text/javascript" src="include/script.js" language="javascript"></script>
</head>
<body>

<? echo $mediainfo->output; ?>

</body>
</html>