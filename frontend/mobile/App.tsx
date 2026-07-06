import React from 'react';
import { AuthProvider } from './src/store/AuthContext';
import { CarritoProvider } from './src/store/CarritoContext';
import AppNavigator from './src/navigation/AppNavigator';
import { useNetworkStatus } from './src/hooks/useNetworkStatus';
import SinConexion from './src/components/SinConexion';
import NetInfo from '@react-native-community/netinfo';

function AppContent() {
  const { isConnected, isChecking } = useNetworkStatus();

  if (isChecking) return null;

  if (!isConnected) {
    return (
      <SinConexion
        onReintentar={() => NetInfo.fetch()}
      />
    );
  }

  return <AppNavigator />;
}

export default function App() {
  return (
    <AuthProvider>
      <CarritoProvider>
        <AppContent />
      </CarritoProvider>
    </AuthProvider>
  );
}