<?php

class Multilingual_Woocommerce_Network_Main_Menu extends Multilingual_Woocommerce_Admin_Page {
 

	public function render_content() {
		echo "<p>This is the main Network page</p>";

		?>
			<table class="form-table">
				<?php $this->render_row( __( 'A row', MULTILINGUAL_WOO_LANG_DOMAIN ), array( &$this, 'render_field' ) ); ?>
			</table>
		<?php
	}

	function render_field() {
		echo "A row field";
	}

}

