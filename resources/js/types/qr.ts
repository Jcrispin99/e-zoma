export interface QrStyle {
  id: number;
  name: string;
  is_default: boolean;
  qr_size: number;
  label_width: number;
  label_height: number;
  show_product_name: boolean;
  show_description: boolean;
  show_price: boolean;
  show_sku: boolean;
  show_barcode_text: boolean;
}
