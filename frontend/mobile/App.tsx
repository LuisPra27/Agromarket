import React from 'react';
import { AuthProvider } from './src/store/AuthContext';
import { CarritoProvider } from './src/store/CarritoContext';
import AppNavigator from './src/navigation/AppNavigator';

export default function App() {
  return (
    <AuthProvider>
      <CarritoProvider>
        <AppNavigator />
      </CarritoProvider>
    </AuthProvider>
  );
}