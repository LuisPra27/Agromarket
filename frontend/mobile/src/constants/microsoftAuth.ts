// Configuración del login con Microsoft (Azure AD / Entra ID).
//
// El Client ID se saca del App Registration creado en https://portal.azure.com
// (App registrations > tu app > Overview > "Application (client) ID").
// Se pasa por variable de entorno para no hardcodearlo ni subirlo al repo.
//
// Agrega esto a tu .env local:
//   EXPO_PUBLIC_MICROSOFT_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
export const MICROSOFT_CLIENT_ID = process.env.EXPO_PUBLIC_MICROSOFT_CLIENT_ID ?? '';

// "common" acepta tanto cuentas institucionales (@live.uleam.edu.ec, @uleam.edu.ec)
// como cuentas personales, siempre que el App Registration sea "multitenant".
export const MICROSOFT_DISCOVERY = {
  authorizationEndpoint: 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
  tokenEndpoint: 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
};

export const MICROSOFT_SCOPES = ['openid', 'profile', 'email', 'User.Read'];