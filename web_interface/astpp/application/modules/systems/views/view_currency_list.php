<?php extend('master.php') ?>
<?php startblock('extra_head') ?>


<script type="text/javascript" language="javascript">
    $(document).ready(function() {
      
        build_grid("currency_grid","",<?php echo $grid_fields; ?>,<?php echo $grid_buttons; ?>);
        $('.checkall').click(function () {
            $('.chkRefNos').attr('checked', this.checked); 
        });
        $("#currency_search_btn").click(function(){
            
            post_request_for_search("currency_grid","","currency_search");
        });        
        $("#id_reset").click(function(){ 
            clear_search_request("currency_grid","");
        });
        
    });
</script>
<?php endblock() ?>

<?php startblock('page-title') ?>
<?= $page_title ?>
<?php endblock() ?>

<?php startblock('content') ?>        
<style>	
	
.pure-button-primary {
/*	color:blue;height:25px;width:150px;background-color: rgb(0, 120, 231);color:white;*/
	width:140px;color:#fff; background-color:#79C447; border-radius:4px; font-family:arial; text-align:center;box-shadow:0px 1px 1px #406826;padding:5px 5px 5px 5px;border:2px #63a139;cursor:pointer;font-family: 'Lato', sans-serif;
}
</style>


<section class="slice color-three">
	<div class="w-section inverse no-padding">
    	<div class="container">
   	    <div class="row">
            	<div class="portlet-content"  id="search_bar" style="cursor:pointer; display:none">
                    	<?php echo $form_search; ?>
    	        </div>

            </div>
        </div>
    </div>
</section>

<section class="slice color-three padding-b-20">
	<div class="w-section inverse no-padding">
    	<div class="container">
        	<div class="row">
                <div class="col-md-12">      
                        <form method="POST" action="del/0/" enctype="multipart/form-data" id="ListForm">
                            <table id="currency_grid" align="left" style="display:none;"></table>
                        </form>
                </div>  
            </div>
        </div>
    </div> </div>
</section>

<?php
endblock();
end_extend();
 
 
