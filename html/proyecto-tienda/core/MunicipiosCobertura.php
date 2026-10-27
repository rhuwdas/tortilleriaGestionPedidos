<?php
/**
 * core/MunicipiosCobertura.php
 *
 * Catálogo compartido de municipios con cobertura de entrega.
 * La cobertura corresponde a Santa Cruz Xoxocotlán y sus municipios
 * colindantes, todos pertenecientes al estado de Oaxaca.
 */

/**
 * Devuelve los municipios con cobertura de entrega.
 *
 * @return string[]
 */
function obtenerMunicipiosCobertura(): array
{
    return [
        'Oaxaca de Juárez',
        'Santa Cruz Xoxocotlán',
        'San Antonio de la Cal',
        'San Agustín de las Juntas',
        'Ánimas Trujano',
        'Santa María Coyotepec',
        'Villa de Zaachila',
        'San Raymundo Jalpan',
        'Cuilápam de Guerrero',
        'San Pedro Ixtlahuaca',
        'Santa María Atzompa',
    ];
}

/**
 * Municipio único de entrega para los pedidos de POS y e-commerce.
 */
function obtenerMunicipioEntrega(): string
{
    return 'Santa Cruz Xoxocotlán';
}

/**
 * Colonias, barrios y fraccionamientos pertenecientes a Santa Cruz Xoxocotlán (Xoxo).
 *
 * @return string[]
 */
function obtenerColoniasXoxocotlan(): array
{
    return [
        'Centro',
        '1a Ampliación de Xoxo',
        '2a Ampliación de Xoxo',
        '20 de Noviembre',
        '21 de Marzo',
        '3 de Mayo',
        '3 de Octubre',
        '3 Ranchos',
        'Aguayo',
        'Agustín Melgar',
        'Ampliación Ex-Hacienda Candiani',
        'Ampliación Indeco',
        'Ampliación Independencia',
        'Ampliación Palestina',
        'Ampliación San Isidro',
        'Anáhuac',
        'Antequera',
        'Arboledas',
        'Arboledas Villas Zaachila',
        'Ayuuc Mexi',
        'Benemérito de las Américas',
        'Benito Juárez',
        'Brasil',
        'Carrasco Altamirano',
        'Colinas de San José',
        'Damnificados',
        'Del Valle',
        'El Chapulín',
        'El Higo',
        'El Mirador',
        'El Nogal',
        'El Nuevo Manantial',
        'El Paragüito',
        'El Paraíso',
        'El Paredón',
        'El Pedregal',
        'El Periodista',
        'El Rosario Xoxocotlán',
        'El Sol',
        'Eliseo Jiménez Ruiz',
        'Emiliano Zapata',
        'Esquipulas Xoxo',
        'Ex-Garita de Xoxo',
        'Ex-Hacienda Candiani',
        'Framboyanes',
        'Framboyanes II',
        'Francisco I. Madero',
        'Francisco Villa',
        'Granjas Aguayo',
        'Guadalupe',
        'Guamuches',
        'Independencia',
        'Insurgentes',
        'Itabiani',
        'Jardines',
        'Jardines de la Ciénega',
        'Jardines de Monte Albán',
        'Jardines del Sur',
        'Jardines Residencial',
        'Jerusalén',
        'Juquilita',
        'La Alianza',
        'La Crucecita',
        'La Escondida',
        'La Floresta',
        'La Florida',
        'La Horqueta',
        'La Oaxaqueña',
        'La Paz',
        'La Soledad',
        'Las Águilas',
        'Las Culturas',
        'Las Palmas',
        'Laureles',
        'Lázaro Cárdenas del Río',
        'Lomas de Buenos Aires',
        'Lomas de Monte Albán',
        'Lomas de Nazareno',
        'Lomas de San Javier',
        'Lomas de Santa Cruz',
        'Los Ángeles',
        'Los Cantaros',
        'Los Danzantes',
        'Los Fresnos',
        'Los Higos',
        'Los Olivos',
        'Los Pinos',
        'Los Puentes Cuaches',
        'Mi Ranchito',
        'Minería',
        'Montebello',
        'Morelos',
        'Noche Buena',
        'Odilón',
        'Ojito de Agua',
        'Olímpica',
        'Ónix',
        'Oriental',
        'Palestina',
        'Panorámica',
        'Paraje la Primavera',
        'Pirámides',
        'Progreso',
        'Real Antequera',
        'Real San Miguel',
        'Reforma Agraria',
        'Residencial Agaves',
        'Residencial del Bosque',
        'Residencial del Prado',
        'Riberas del Atoyac',
        'Rinconadas Villas Xoxo',
        'Rufino Tamayo',
        'SAHOP',
        'San Antonio Arrazola',
        'San Francisco Javier',
        'San Ignacio',
        'San Isidro Monjas',
        'San Jesús Nazareno',
        'San José Comunal',
        'San Juan Bautista la Raya',
        'San Miguel Arcángel',
        'San Pedro el Carrizal',
        'Santa Alicia',
        'Santa Clara',
        'Santa Cruz Xoxocotlán',
        'Santa Elena',
        'Satélite',
        'Símbolos Patrios',
        'Sor Juana Inés de la Cruz',
        'Tenochtitlán',
        'Trabajadores del Instituto Tecnológico',
        'Unión',
        'Villas Dalias',
        'Villas del Bosque',
        'Villas Laureles',
        'Villas Los Ángeles',
        'Villas Xoxo',
        'Vista del Valle',
        'Vista Real',
    ];
}

/**
 * Normaliza un municipio a minúsculas y sin acentos para compararlo.
 */
function normalizarMunicipioCobertura(string $municipio): string
{
    $municipioNormalizado = mb_strtolower(trim($municipio), 'UTF-8');
    $municipioNormalizado = strtr($municipioNormalizado, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
    ]);

    return preg_replace('/\s+/', ' ', $municipioNormalizado) ?? '';
}

/**
 * Indica si un municipio se encuentra dentro de la cobertura de entrega.
 */
function municipioTieneCobertura(string $municipio): bool
{
    $municipioNormalizado = normalizarMunicipioCobertura($municipio);
    if ($municipioNormalizado === '') {
        return false;
    }

    // Compatibilidad con registros previos que guardaron solo "Oaxaca".
    if ($municipioNormalizado === 'oaxaca') {
        return true;
    }

    foreach (obtenerMunicipiosCobertura() as $municipioCobertura) {
        if (normalizarMunicipioCobertura($municipioCobertura) === $municipioNormalizado) {
            return true;
        }
    }

    return false;
}
