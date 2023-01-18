<?php

namespace dnj\Invoice\traits;

use dnj\Invoice\Models\Invoice;
use dnj\Invoice\Models\Product;
use dnj\Number\Contracts\INumber;
use dnj\Number\Number;

trait ProductBuilder {
	public function buliderInvoiceProducts ( array $products ): array {
		$items = [];
		foreach ( $products as $key => $product ) {
			if ( isset($product[ 'title' ]) ) {
				$items[ $key ][ 'title' ] = $product[ 'title' ];
			}
			if ( isset($product[ 'price' ]) ) {
				$items[ $key ][ 'price' ] = $product[ 'price' ];
			}
			if ( isset($product[ 'discount' ]) ) {
				$items[ $key ][ 'discount' ] = $product[ 'discount' ];
			}
			if ( isset($product[ 'count' ]) ) {
				$items[ $key ][ 'count' ] = $product[ 'count' ];
			}
			if ( isset($product[ 'currencyId' ]) ) {
				$items[ $key ][ 'currency_id' ] = $product[ 'currencyId' ];
			}
			if ( isset($product[ 'distributionPlan' ]) ) {
				$items[ $key ][ 'distribution_plan' ] = $product[ 'distributionPlan' ];
			}
			if ( isset($product[ 'distribution' ]) ) {
				$items[ $key ][ 'distribution' ] = $product[ 'distribution' ];
			}
			if ( isset($product[ 'description' ]) ) {
				$items[ $key ][ 'description' ] = $product[ 'distribution' ];
			}
			if ( isset($product[ 'meta' ]) ) {
				$items[ $key ][ 'meta' ] = $product[ 'meta' ];
			}
		}
		
		return $items;
	}
	
	public function recalculateTotalAmount ( array $products ):INumber {
		$total = 0;
		foreach ( $products as $product ) {
			$total += ( ( $product[ 'count' ] * $product[ 'price' ] ) - $product[ 'discount' ] );
		}
		
		return Number::fromInt($total);
	}
	
	public function updateInvoiceProduct ( array $products , Invoice $invoice ) {
		$product_valid_ids = [];
		foreach ( $products as $product ) {
			if ( isset($product[ 'id' ]) ) {
				$product_valid_ids[] = $product[ 'id' ];
			}
		}
		$removeableProducts = Product::query()
									 ->whereNotIn('id' , $product_valid_ids)
									 ->get();
		if ( $removeableProducts->isNotEmpty() ) {
			Product::query()
				   ->whereNotIn('id' , $product_valid_ids)
				   ->delete();
		}
		foreach ( $products as $key => $product ) {
			if ( isset($product[ 'id' ]) ) {
				$record = Product::query()
								 ->findOrFail($product[ 'id' ]);
				if ( isset($product[ 'price' ]) ) {
					$record->price = $product[ 'price' ];
				}
				if ( isset($product[ 'discount' ]) ) {
					$record->discount = $product[ 'discount' ];
				}
				if ( isset($product[ 'count' ]) ) {
					$record->count = $product[ 'count' ];
				}
				if ( isset($product[ 'title' ]) ) {
					$record->title = $product[ 'title' ];
				}
				if ( isset($product[ 'currencyId' ]) ) {
					$record->currency_id = $product[ 'currencyId' ];
				}
				if ( isset($product[ 'meta' ]) ) {
					$record->meta = $product[ 'meta' ];
				}
				if ( isset($product[ 'distributionPlan' ]) ) {
					$record->distribution_plan = $product[ 'distributionPlan' ];
				}
				if ( isset($product[ 'distribution' ]) ) {
					$record->distribution = $product[ 'distribution' ];
				}
				if ( isset($product[ 'description' ]) ) {
					$record->description = $product[ 'description' ];
				}
				$record->save();
				unset($products[ $key ]);
			}
		}
		if ( 0 != count($products) ) {
			$products = $this->buliderInvoiceProducts($products);
			$invoice->products()
					->createMany($products);
		}
	}
}
