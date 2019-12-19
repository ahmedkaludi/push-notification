<?php
add_filter('pre_get_document_title', 'pn_serpage_name', 9999, 1);
function pn_serpage_name($title){
	$title = 'AMP Subscription';
	return $title;
}
get_header();
?>
	<style>
		.pn-full-height {
                height: 80vh;
            }
        .pn-flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
                width:100%;
            }
        .pn-content {
            text-align: center;
        }
        .pn-title {
		    font-size: 40px;
		}
	</style>
	<section id="primary" class="content-area pn-flex-center pn-full-height">
		<main id="main" class="site-main pn-content ">
			<div class="pn-title">
				Subscribe Notification
			</div>
			<p> Click on allow to stay updated </p>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();