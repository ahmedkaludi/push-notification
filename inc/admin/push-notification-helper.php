<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Push_Notification_Helper {
    
    public function pn_expanded_allowed_tags() {
        
        $my_allowed = wp_kses_allowed_html( 'post' );
        // form fields - input
        $my_allowed['input'] = array(
                'class'        => array(),
                'id'           => array(),
                'name'         => array(),
                'value'        => array(),
                'type'         => array(),
                'style'        => array(),
                'placeholder'  => array(),
                'maxlength'    => array(),
                'checked'      => array(),
                'readonly'     => array(),
                'disabled'     => array(),
                'width'        => array(),
                'data-id'      => array(),
                'media-id'     => array(),
                'provider_type'=> array(),
                
        ); 
        //number
        $my_allowed['number'] = array(
            
                'class'        => array(),
                'id'           => array(),
                'name'         => array(),
                'value'        => array(),
                'type'         => array(),
                'style'        => array(),                    
                'width'        => array(),
                
        ); 
        //textarea
         $my_allowed['textarea'] = array(
                'class' => array(),
                'id'    => array(),
                'name'  => array(),
                'value' => array(),
                'type'  => array(),
                'style' => array(),
                'rows'  => array(),                                                            
        );       
         //amp tag
         $my_allowed['amp-ad'] = array(
                'class'                     => array(),
                'width'                     => array(),
                'height'                    => array(),
                'type'                      => array(),
                'data-slot'                 => array(),                 
                'data-ad-client'            => array(),
                'data-ad-slot'              => array(),
                'data-tagtype'              => array(),
                'data-cid'                  => array(),
                'data-crid'                 => array(),
                'data-mid'                  => array(),
                'data-block-id'             => array(),
                'data-html-access-allowed'  => array(),
                'data-property'             => array(),
                'data-zone'                 => array(),
                'data-json'                 => array(),
                'fallback'                  => array(),
        );
         $my_allowed['amp-pixel'] = array(                    
                'src'     => array(),
                'layout'  => array(),                    
        );
         $my_allowed['amp-embed'] = array(
                'class'             => array(),
                'width'             => array(),
                'height'            => array(),
                'heights'           => array(),
                'type'              => array(),
                'layout'            => array(),                 
                'data-publisher'    => array(),
                'data-mode'         => array(),
                'data-placement'    => array(),
                'data-target_type'  => array(),
                'data-article'      => array(),
                'data-url'          => array(),                    
                'data-widgetids'    => array(),                    
                'data-publisherid'  => array(),                    
                'data-websiteid'    => array(),                    
        );
         $my_allowed['amp-img'] = array(
                'class'     => array(),
                'id'        => array(),
                'width'     => array(),
                'height'    => array(),
                'type'      => array(),
                'src'       => array(), 
                'on'        => array(), 
                'role'      => array(), 
                'tabindex'  => array(), 
                'layout'    => array(), 
        );
         $my_allowed['amp-ad-exit'] = array(
                'id' => array(),                    
         );
         $my_allowed['amp-auto-ads'] = array(
                'type'           => array(),
                'id'             => array(),
                'data-ad-client' => array(),
                'height'         => array(),
                'width'          => array(),             
        );
         $my_allowed['amp-sticky-ad'] = array(
                'layout' => array(),
                'id'     => array(),                             
        );
         $my_allowed['amp-list'] = array(
                'width'  => array(),
                'height' => array(),
                'layout' => array(),
                'src'    => array(),
                'width'  => array(), 
                'id'     => array(), 
        );
         $my_allowed['amp-live-list'] = array(                    
                'data-max-items-per-page'  => array(),
                'data-poll-interval'       => array(), 
                'id'                       => array(), 
        );
         $my_allowed['amp-app-banner'] = array(                    
                'layout'    => array(),                    
                'id'        => array(), 
        );
         $my_allowed['amp-carousel'] = array(                    
                'width'                           => array(),                    
                'height'                          => array(), 
                'id'                              => array(), 
                'layout'                          => array(), 
                'type'                            => array(), 
                'data-next-button-aria-label'     => array(), 
                'data-previous-button-aria-label' => array(),
                'delay'                           => array(),
                'loop'                            => array(),
                'autoplay'                        => array(),
                'controls'                        => array(),
             
        );
         $my_allowed['amp-iframe'] = array(                    
                'width'         => array(), 
                'height'        => array(), 
                'sandbox'       => array(), 
                'layout'        => array(), 
                'frameborder'   => array(),
                'src'           => array(),                 
                'id'            => array(), 
        );
         $my_allowed['amp-image-lightbox'] = array(                    
                'layout'    => array(), 
                'height'    => array(),                                         
                'id'        => array(), 
        );
         $my_allowed['amp-layout'] = array(                    
                'layout'  => array(), 
                'width'   => array(),   
                'height'  => array(),   
                'id'      => array(), 
        );
         $my_allowed['amp-3d-gltf'] = array(                    
                'layout'        => array(), 
                'width'         => array(),   
                'height'        => array(),   
                'id'            => array(), 
                'antialiasing'  => array(), 
                'src'           => array(),                  
        );
         $my_allowed['amp-anim'] = array(                    
                'layout'        => array(), 
                'width'         => array(),   
                'height'        => array(),   
                'id'            => array(), 
                'srcset'        => array(), 
                'src'           => array(),                  
        );
         $my_allowed['amp-imgur'] = array(                    
                'data-imgur-id' => array(), 
                'layout'        => array(),   
                'width'         => array(),   
                'height'        => array(), 
                'id'            => array(),                                   
        );
         $my_allowed['amp-animation'] = array(                                        
                'layout'          => array(),   
                'duration'        => array(),   
                'delay'           => array(), 
                'endDelay'        => array(),
                'iterations'      => array(),
                'iterationStart'  => array(),
                'easing'          => array(),
                'direction'       => array(),
                'fill'            => array(),   
        );
         
        // select
        $my_allowed['select'] = array(
                'class'     => array(),
                'id'        => array(),
                'name'      => array(),
                'value'     => array(),
                'type'      => array(),
                'required'  => array(),
                'multiple'  => array(),
                'style'     => array(),
        );
        
        $my_allowed['iframe'] = array(
                'class'   => array(),
                'id'      => array(),
                'src'     => array(),
                'height'  => array(),
                'width'   => array(),                                                            
        );
                    
        $my_allowed['tr'] = array(
                'class'  => array(),
                'id'     => array(),
                'name'   => array(),                    
        );
        $my_allowed['div'] = array(
                'class'             => array(),
                'id'                => array(),
                'data-id'           => array(), 
                'data-mantis-zone'  => array(), 
            
        );
        //  options
        $my_allowed['option'] = array(
                'selected' => array(),
                'value'    => array(),
        );
        $my_allowed['optgroup'] = array(
                'label'   => array(),
                'data-id' => array(),
        );
        // style
        $my_allowed['style'] = array(
                'types' => array(),
        );
        // allow script
        $my_allowed['script'] = array(
                'src'               => array(),
                'type'              => array(),
                'data-width'        => array(),
                'data-height'       => array(),                                
                'async'             => array(),
                'crossorigin'       => array(),
                'defer'             => array(),
                'importance'        => array(),
                'integrity'         => array(),
                'nomodule'          => array(),
                'nonce'             => array(),                   
                'text'              => array(),
                'charset'           => array(),
                'language'          => array(), 
                'data-adfscript'    => array(), 
        );
                    
        return $my_allowed;
    }
}