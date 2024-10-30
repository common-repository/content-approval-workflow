<div class="wrap">
	<h2>
		<?php esc_html_e( 'Content Approval Workflow Settings', 'content-approval-workflow' ); ?>
	</h2>

	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( $caw_tabs as $caw_tab => $tab_data ) {
			echo '<button class="nav-tab" data-id="#caw-' . esc_attr( $caw_tab ) . '">' . esc_html( ucfirst( $tab_data['tab_name'] ) ) . '</button>';
		}
		?>
	</h2>

	<?php foreach ( $caw_tabs as $caw_tab => $tab_data ) : ?>
		<div id="caw-<?php echo esc_attr( $caw_tab ); ?>" class="caw-tab-content">
			<form method="POST" action="options.php">
				<?php settings_fields( 'caw_' . $caw_tab . '_settings' ); ?>
				<?php do_action( 'caw_before_settings_section_' . $caw_tab ); ?>
				<?php do_settings_sections( 'caw_' . $caw_tab . '_settings' ); ?>

				<?php
				// translators: %s is a placeholder for the specific setting name.
				submit_button(
					sprintf(
						'%s %s',
						esc_html__( 'Save', 'content-approval-workflow' ),
						$tab_data['label'],
					)
				);
				?>

				<?php do_action( 'caw_after_settings_section_' . $caw_tab ); ?>
			</form>
		</div>
	<?php endforeach; ?>
	<?php do_action( 'caw_after_settings_form' ); ?>
</div>
