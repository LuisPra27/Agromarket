import { useState, useEffect } from 'react';
import api from '../services/api';
import { Producto, Categoria } from '../types';

export function useProductos(categoriaId?: number, buscar?: string) {
  const [productos, setProductos] = useState<Producto[]>([]);
  const [cargando, setCargando] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    cargar();
  }, [categoriaId, buscar]);

  const cargar = async () => {
    setCargando(true);
    setError(null);
    try {
      const params: Record<string, any> = {};
      if (categoriaId) params.categoria_id = categoriaId;
      if (buscar) params.buscar = buscar;

      const response = await api.get<Producto[]>('/productos', { params });
      setProductos(response.data);
    } catch (e: any) {
      setError('No se pudieron cargar los productos.');
    } finally {
      setCargando(false);
    }
  };

  return { productos, cargando, error, recargar: cargar };
}

export function useCategorias() {
  const [categorias, setCategorias] = useState<Categoria[]>([]);
  const [cargando, setCargando] = useState(true);

  useEffect(() => {
    api.get<Categoria[]>('/categorias')
      .then(r => setCategorias(r.data))
      .finally(() => setCargando(false));
  }, []);

  return { categorias, cargando };
}