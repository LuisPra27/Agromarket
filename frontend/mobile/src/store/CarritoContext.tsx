import React, { createContext, useContext, useState } from 'react';
import { Producto, ItemCarrito } from '../types';

interface CarritoContextType {
  items: ItemCarrito[];
  agregar: (producto: Producto) => void;
  quitar: (productoId: number) => void;
  cambiarCantidad: (productoId: number, cantidad: number) => void;
  limpiar: () => void;
  total: number;
  cantidadTotal: number;
}

const CarritoContext = createContext<CarritoContextType>({} as CarritoContextType);

export const CarritoProvider = ({ children }: { children: React.ReactNode }) => {
  const [items, setItems] = useState<ItemCarrito[]>([]);

  const agregar = (producto: Producto) => {
    setItems(prev => {
      const existe = prev.find(i => i.producto.id === producto.id);
      if (existe) {
        return prev.map(i =>
          i.producto.id === producto.id
            ? { ...i, cantidad: Math.min(i.cantidad + 1, producto.stock) }
            : i
        );
      }
      return [...prev, { producto, cantidad: 1 }];
    });
  };

  const quitar = (productoId: number) => {
    setItems(prev => prev.filter(i => i.producto.id !== productoId));
  };

  const cambiarCantidad = (productoId: number, cantidad: number) => {
    if (cantidad <= 0) {
      quitar(productoId);
      return;
    }
    setItems(prev =>
      prev.map(i =>
        i.producto.id === productoId ? { ...i, cantidad } : i
      )
    );
  };

  const limpiar = () => setItems([]);

  const total = items.reduce(
    (acc, i) => acc + Number(i.producto.precio) * i.cantidad, 0
  );

  const cantidadTotal = items.reduce((acc, i) => acc + i.cantidad, 0);

  return (
    <CarritoContext.Provider value={{
      items, agregar, quitar, cambiarCantidad, limpiar, total, cantidadTotal
    }}>
      {children}
    </CarritoContext.Provider>
  );
};

export const useCarrito = () => useContext(CarritoContext);