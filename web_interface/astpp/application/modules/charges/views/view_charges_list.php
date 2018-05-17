<?php extend('master.php') ?>
<?php startblock('extra_head') ?>


<script type="text/javascript" language="javascript">
    $(document).ready(function() {
      
        build_grid("charges_grid","",<?php echo $grid_fields; ?>,<?php echo $grid_buttons; ?>);
         $('.checkall').click(function () {
            $('.chkRefNos').attr('checked', this.checked); //if you want to select/deselect checkboxes use this
        });
       $("#charges_search_btn").click(function(){
           
            post_request_for_search("charges_grid","","charges_search");
        });        
        $("#id_reset").click(function(){
            clear_search_request("charges_grid","");
        });
        
    });
</script>

<?php endblock() ?>

<?php startblock('page-title') ?>
    <?= $page_title ?>
<?php endblock() ?>

<?php startblock('content') ?>        
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
                            <table id="charges_grid" align="left" style="display:none;"></table>
                        </form>
                </div>  
            </div>
        </div>
    </div>
</section>

<?php

endblock();
end_extend();
