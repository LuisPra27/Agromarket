import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

export default function RepartidorTabsScreen() {
  return (
    <View style={styles.container}>
      <Text>Repartidor Tabs Screen</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});