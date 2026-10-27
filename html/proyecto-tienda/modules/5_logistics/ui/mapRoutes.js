/** Construcción de enlaces de navegación a la dirección de entrega. */
const MapRoutes = {
	crearEnlace(entrega) {
		const url = new URL('https://www.google.com/maps/dir/');
		url.searchParams.set('api', '1');

		if (entrega.latitud !== null && entrega.longitud !== null) {
			url.searchParams.set('destination', `${entrega.latitud},${entrega.longitud}`);
		} else {
			url.searchParams.set('destination', entrega.direccion_completa);
		}

		return url.toString();
	},
};
