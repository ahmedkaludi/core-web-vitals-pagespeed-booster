<?php
add_filter('web_vital_css_whitelist_css', 'webvital_divi_css_animat');
function webvital_divi_css_animat($css){
	$css .= '.et_pb_animation_left.et-animated {
				opacity: 1;
				-webkit-animation: fadeLeft 1s 1 cubic-bezier(0.77, 0, 0.175, 1);
				-moz-animation: fadeLeft 1s 1 cubic-bezier(0.77, 0, 0.175, 1);
				-o-animation: fadeLeft 1s 1 cubic-bezier(0.77, 0, 0.175, 1);
				animation: fadeLeft 1s 1 cubic-bezier(0.77, 0, 0.175, 1);
			}';
	return $css;
}