<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Social Integration'); ?></h1>

<?php include('connection_functions.php'); ?>

<style>
   .Conneciton-Header * {
      line-height: 48px;
      position: relative;
   }
   
   .Connection-Name {
      font-size: 28px;
   }
   
   .IconWrap {
      margin-right: 10px;
   }
   
   .IconWrap img {
      height: 48px;
      width: 48px;
      vertical-align: bottom;
      border-radius: 3px;
   }
   
   .DataList-Connections .Connection-Header {
      overflow: hidden;
      position: relative;
   }
   
   .DataList-Connections .ProfilePhoto {
      vertical-align: text-bottom;
   }

   .Connection-Connect {
       position: absolute;
       right: 0;
       bottom: 0;
       padding: 5px;
   }

   .Gloss.Connected {
      position: absolute;
      bottom: 5px;
      left: 250px;
   }
</style>

<ul class="DataList DataList-Connections"><?php
   
   foreach ($this->Data('Connections') as $Key => $Row) {
      WriteConnection($Row);
   }
   
?></ul>