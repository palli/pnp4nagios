<?php if (!empty($services)) { ?>
<div class="ui-widget">
 <div class="ui-widget-header">
 Services
 </div>
<div class="p4 ui-widget-content ui-corner-bottom">
<?php 
foreach($services as $service){
	echo html::anchor($this->uri->string().
		"?host=".$host.
		"&srv=".$service['name'],
		$service['servicedesc']."<br>");
	
}
?>
</div>
</div>

<?php } ?>
