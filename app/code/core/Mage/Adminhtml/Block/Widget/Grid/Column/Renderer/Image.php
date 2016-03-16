<?php class Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Image extends 
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
        protected static $showImagesUrl = null;
        protected static $showByDefault = null;
        protected static $width = null;
        protected static $height = null;

        public function __construct() {
            if(self::$showImagesUrl == null)
                self::$showImagesUrl = 1;
            if(self::$showByDefault == null)
                self::$showByDefault = 1;
            if(self::$width == null)
                self::$width = '60px';
            if(self::$height == null)
                self::$height = '60px';
        }

        /**
         * Renders grid column
         *
         * @param   Varien_Object $row
         * @return  string
         */
        public function render(Varien_Object $row) {
            return $this->_getValue($row);
        } 

        /*
        public function renderProperty(Varien_Object $row) {
            $val = $row->getData($this->getColumn()->getIndex());
            $val = Mage::helper('imagebyurl')->getImageUrl($val);
            $out = parent::renderProperty(). ' onclick="showImage('.$val.')" ';
            return $out;
        }    
        */

        protected function _getValue(Varien_Object $row) {
            //$row->getEntityId();
            $dored = false;

            if ($getter = $this->getColumn()->getGetter()) {
                $val = $row->$getter();
            }

            $val = $val2 = $row->getData($this->getColumn()->getIndex());
            $val = str_replace("no_selection", "", $val);
            $val2 = str_replace("no_selection", "", $val2);
            //$url = Mage::helper('adminhtml')->getImageUrl($val);
            $url = "http://127.0.0.1/magento/media/catalog/product".$val;

            /*if(!Mage::helper('adminhtml')->getFileExists($val)) {
                $dored =true;
                $val .= "[!]";
            }*/

            if(strpos($val, "placeholder/")) {
                $dored = true;
            }

            $filename = substr($val2, strrpos($val2, "/")+1, 
                strlen($val2)-strrpos($val2, "/")-1);
            $_url = $url;
            //echo $_SERVER['SERVER_NAME'];


            if(!self::$showImagesUrl) $filename = '';
                if($dored) {
                    $val = "<span style=\"color:red\" id=\"img\">$filename</span>";
                } 

                else {
                    $val = "<span style=\"color:#888;\">". $filename ."</span>";
                }

                if(empty($val2) ) {
                    $out = "<center>" . $this->__("(no image)") . "</center>";
                } 

                else {
                    $out = $val. '<center><a href="'.$_url.'" target="_blank" 
                        id="imageurl">';
                }

                if(self::$showByDefault && !empty($val2) ) {
                    $out .= "<img src=". $url ." width='60px' ";
                    $out .=" />";
                }

                $out .= '</a></center>';

                return $out;

            }
        }