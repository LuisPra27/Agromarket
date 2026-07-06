import { useState, useEffect } from 'react';
import NetInfo, { NetInfoState } from '@react-native-community/netinfo';

export function useNetworkStatus() {
  const [isConnected, setIsConnected] = useState<boolean>(true);
  const [isChecking, setIsChecking] = useState(true);

  useEffect(() => {
    const unsubscribe = NetInfo.addEventListener((state: NetInfoState) => {
      setIsConnected(state.isConnected ?? true);
      setIsChecking(false);
    });

    NetInfo.fetch().then(state => {
      setIsConnected(state.isConnected ?? true);
      setIsChecking(false);
    });

    return unsubscribe;
  }, []);

  return { isConnected, isChecking };
}