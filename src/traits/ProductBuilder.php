<?php

namespace dnj\Invoice\traits;
trait ProductBuilder {
	public function buliderInvoiceProducts ( array $products ): array {
		foreach ( $products as $key => $product ) {
			
			if ( isset($product[ 'price' ]) ) {
				$product[ $key ][ 'total_amount' ] = $product[ 'count' ] * $product[ 'price' ];
			} else {
				$product[ $key ][ 'total_amount' ] = $product[ 'count' ] * $product[ 'discount' ];
			}
		}
		
		return $products;
	}
	
	public function calculationTotalAmountProduct ( array $products ) {
		
		$amount = 0;
		foreach ( $products as $product ) {
			$amount += $product[ 'total_amount' ];
		}
		
		return $amount;
	}
}