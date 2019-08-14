<?php
/**
 *  Swipezoom_InternationalShipping_Model_Sales_Order_Pdf_Items_Invoice_Default
 *
 * @author Swipezoom
 */
class Swipezoom_InternationalShipping_Model_Sales_Order_Pdf_Items_Invoice_Default extends Mage_Sales_Model_Order_Pdf_Items_Invoice_Default
{
	public function draw()
    {
        $order  = $this->getOrder();
        $item   = $this->getItem();
        $pdf    = $this->getPdf();
        $page   = $this->getPage();
		$linenumber   = $this->getLineno();
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
            'text' => Mage::helper('core/string')->str_split($item->getName(), 35, true, true),
            'feed' => 35,
        ));

        // draw SKU
        $lines[0][] = array(
            'text'  => Mage::helper('core/string')->str_split($this->getSku($item), 17),
            'feed'  => 170,
            'align' => 'right'
        );

        // draw QTY
        $lines[0][] = array(
            'text'  => $item->getQty() * 1,
            'feed'  => 435,
            'align' => 'right'
        );

        // draw item Prices
        $i = 0;
        $prices = $this->getItemPricesForDisplay();
        $feedPrice = 395;
        $feedSubtotal = $feedPrice + 170;
        foreach ($prices as $priceData){
            if (isset($priceData['label'])) {
                // draw Price label
                $lines[$i][] = array(
                    'text'  => $priceData['label'],
                    'feed'  => $feedPrice,
                    'align' => 'right'
                );
                // draw Subtotal label
                $lines[$i][] = array(
                    'text'  => $priceData['label'],
                    'feed'  => $feedSubtotal,
                    'align' => 'right'
                );
                $i++;
            }
            // draw Price
            $lines[$i][] = array(
                'text'  => $priceData['price'],
                'feed'  => $feedPrice,
                'font'  => 'bold',
                'align' => 'right'
            );
            // draw Subtotal
            $lines[$i][] = array(
                'text'  => $priceData['subtotal'],
                'feed'  => $feedSubtotal,
                'font'  => 'bold',
                'align' => 'right'
            );
            $i++;
        }

        // draw Tax
        $lines[0][] = array(
            'text'  => $order->formatPriceTxt($item->getTaxAmount()),
            'feed'  => 495,
            'font'  => 'bold',
            'align' => 'right'
        );

        // custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = array(
                    'text' => Mage::helper('core/string')->str_split(strip_tags($option['label']), 40, true, true),
                    'font' => 'italic',
                    'feed' => 35
                );

                if ($option['value']) {
                    if (isset($option['print_value'])) {
                        $_printValue = $option['print_value'];
                    } else {
                        $_printValue = strip_tags($option['value']);
                    }
                    $values = explode(', ', $_printValue);
                    foreach ($values as $value) {
                        $lines[][] = array(
                            'text' => Mage::helper('core/string')->str_split($value, 30, true, true),
                            'feed' => 40
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
		