// ─── Modelos ────────────────────────────────────────────
export interface Usuario {
  id: number;
  cedula: string | null;
  nombre_completo: string;
  correo: string;
  telefono: string | null;
  expo_push_token?: string | null;
  rol: 'cliente' | 'administrador';
  estado_repartidor: 'no_postulado' | 'pendiente' | 'aprobado' | 'rechazado';
  facultad: string | null;
  balance: number;
}

export interface Categoria {
  id: number;
  nombre: string;
  descripcion: string | null;
}

export interface Producto {
  id: number;
  categoria_id: number;
  nombre: string;
  precio: number;
  stock: number;
  imagen_url: string | null;
  categoria?: Categoria;
}

export interface DetallePedido {
  id: number;
  pedido_id: number;
  producto_id: number;
  cantidad: number;
  precio_unitario: number;
  subtotal: number;
  producto?: Producto;
}

export interface Pedido {
  id: number;
  cliente_id: number;
  repartidor_id: number | null;
  total: number;
  metodo_entrega: 'retiro' | 'delivery';
  estado:
    | 'pendiente_validacion'
    | 'rechazado'
    | 'preparando'
    | 'listo_para_delivery'
    | 'en_camino'
    | 'entregado'
    | 'cancelado';
  comprobante_pago_url: string | null;
  codigo_qr_hash: string | null;
  punto_encuentro: string | null;
  pin_x: number | null;
  pin_y: number | null;
  motivo_cancelacion: string | null;
  detalles?: DetallePedido[];
  cliente?: Usuario;
  repartidor?: Usuario | null;
  created_at?: string;
  updated_at?: string;
}

export interface PedidoListoParaDeliveryEvent {
  id: number;
  total: number;
  punto_encuentro: string | null;
  cliente: {
    nombre_completo: string | null;
  };
  detalles: Array<{
    cantidad: number;
    producto: {
      nombre: string | null;
    };
  }>;
}

// ─── Carrito (estado local, no viene de la API) ──────────
export interface ItemCarrito {
  producto: Producto;
  cantidad: number;
}

// ─── Respuestas de API ───────────────────────────────────
export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface AuthResponse {
  token: string;
  usuario: Usuario;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}