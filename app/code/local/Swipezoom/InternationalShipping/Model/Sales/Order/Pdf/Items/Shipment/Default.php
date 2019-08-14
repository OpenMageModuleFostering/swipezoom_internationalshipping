<?php
/**
 *  Swipezoom_InternationalShipping_Model_Sales_Order_Pdf_Items_Shipment_Default
 *
 * @author Swipezoom
 */

class Swipezoom_InternationalShipping_Model_Sales_Order_Pdf_Items_Shipment_Default extends Mage_Sales_Model_Order_Pdf_Items_Shipment_Default
{
    /**
     * Draw item line
     */
    public function draw()
    {
        $item   = $this->getItem();
        $pdf    = $this->getPdf();
        $page   = $this->getPage();
		$linenumber   = $this->getLineno();
		$order  = $this->getOrder();
        $lines  = array();
		$swipwzoomorder = $order->getSwipezoomOrderNumberTemp();
		$count = Mage::getModel('internationalshipping/packingdetail')->getCollection()->addFieldToFilter('swipezoomorderid',$swipwzoomorder);
		if($swipwzoomorder && count($count)){
				
			 // draw Product name
        $lines[0] = array(array(
            'text' => $linenumber,
            'feed' => 35,
        ));	
			
		  // draw QTY
        $lines[0][] = array(
            'text'  => $item->getBoxcode(),
            'feed'  => 170,
            'align' => 'right'
        );
			
			 // draw SKU
        $lines[0][] = array(
            'text'  => $item->getBoxno(),
            'feed'  => 265,
            'align' => 'right'
        );
		
		
		
		  // draw QTY
        $lines[0][] = array(
            'text'  => Mage::helper('core/string')->str_split($this->getProname(), 35, true, true),
            'feed'  => 350,
            'align' => 'right'
        );
		
		  // draw QTY
        $lines[0][] = array(
            'text'  => $item->getProductcode(),
            'feed'  => 465,
            'align' => 'right'
        );
		
		  // draw QTY
        $lines[0][] = array(
            'text'  => $item->getProductqty(),
            'feed'  => 565,
            'align' => 'right'
        );
				
				
				
				
				
		}else{
        // draw Product name
        $lines[0] = array(array(
            'text' => Mage::helper('core/string')->str_split($item->getName(), 60, true, true),
            'feed' => 100,
        ));

        // draw QTY
        $lines[0][] = array(
            'text'  => $item->getQty()*1,
            'feed'  => 35
        );

        // draw SKU
        $lines[0][] = array(
            'text'  => Mage::helper('core/string')->str_split($this->getSku($item), 25),
            'feed'  => 565,
            'align' => 'right'
        );

        // Custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = array(
                    'text' => Mage::helper('core/string')->str_split(strip_tags($option['label']), 70, true, true),
                    'font' => 'italic',
                    'feed' => 110
                );

                // draw options value
                if ($option['value']) {
                    $_printValue = isset($option['print_value'])
                        ? $option['print_value']
                        : strip_tags($option['value']);
                    $values = explode(', ', $_printValue);
                    foreach ($values as $value) {
                        $lines[][] = array(
                            'text' => Mage::helper('core/string')->str_split($value, 50, true, true),
                            'feed' => 115
                        );
                    }
                }
            }
        }
		}

        $lineBlock = array(
            'lines'  => $lines,
            'height' => 20
        );

        $page = $pdf->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $this->setPage($page);
    }
}
