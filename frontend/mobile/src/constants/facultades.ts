// Facultades del campus principal de Manta, según la estructura oficial
// vigente en https://carreras.uleam.edu.ec (no incluye extensiones/sedes
// como Sucre, Chone, El Carmen, Pedernales, Pichincha, Tosagua o Santo
// Domingo, ya que son ubicaciones físicas distintas al campus del delivery).
export const FACULTADES = [
  'Facultad Ciencias de la Salud',
  'Facultad Ciencias Administrativas, Contables y Comercio',
  'Facultad de Educación, Turismo, Artes y Humanidades',
  'Facultad Ingeniería, Industria y Construcción',
  'Facultad Ciencias de la Vida y Tecnologías',
  'Facultad Ciencias Sociales, Derecho y Bienestar',
] as const;

export type Facultad = (typeof FACULTADES)[number];