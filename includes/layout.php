<?php if(!isset($pageTitle)) $pageTitle="Admin"; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= $pageTitle ?> — Readiwork Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body{margin:0;background:#020617;color:#f1f5f9;font-family:Inter}
.wrapper{display:flex}
.sidebar{
 width:260px;min-height:100vh;background:#020617;
 border-right:1px solid #1f2937;padding:24px
}
.sidebar h3{font-weight:900;margin-bottom:30px}
.sidebar a{
 display:block;padding:10px 14px;color:#cbd5f5;
 text-decoration:none;border-radius:12px;margin-bottom:4px
}
.sidebar a:hover{background:#111827;color:#22c55e}
.section{font-size:11px;color:#64748b;margin:18px 0 8px}
.content{flex:1;padding:40px}
.card-ui{
 background:#111827;border:1px solid #1f2937;
 border-radius:18px;padding:25px
}
</style>
</head>
<body>
<div class="wrapper">
<?php include __DIR__.'/sidebar.php'; ?>
<div class="content">
