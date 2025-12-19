<?php

// Importamos el manejador del CSV (persistencia de datos)
require_once __DIR__ . '/../utils/CsvDataHandler.php';

class ProductoController
{
    // Objeto que se encarga de leer y escribir en el CSV
    private CsvDataHandler $db;

    /**
     * Constructor
     * Inicializa el CsvDataHandler con la ruta del fichero productos.csv
     */
    public function __construct()
    {
        $csvFile = __DIR__ . '/../../data/productos.csv';
        $this->db = new CsvDataHandler($csvFile);
    }

    /**
     * Genera una respuesta de éxito en formato JSON
     * Se usa para no repetir siempre la misma estructura
     */
    private function ok(int $code, string $message, $data = null): array
    {
        $resp = [
            'status' => 'success',
            'code' => $code,
            'message' => $message
        ];

        // El campo data solo se añade si hay información que devolver
        if ($data !== null) {
            $resp['data'] = $data;
        }

        return $resp;
    }

    /**
     * Genera una respuesta de error en formato JSON
     */
    private function fail(int $code, string $message): array
    {
        return [
            'status' => 'error',
            'code' => $code,
            'message' => $message
        ];
    }

    /**
     * GET /public/producto/get
     * Devuelve todos los productos almacenados en el CSV
     */
    public function getAllProductos(): array
    {
        return $this->ok(
            200,
            'Listado de productos',
            $this->db->readAllAsArrays()
        );
    }

    /**
     * GET /public/producto/get/{id}
     * Devuelve un producto concreto por su ID
     */
    public function getProductoById($id): array
    {
        // Convertimos el parámetro a entero
        $id = (int)$id;

        // Buscamos el producto en el CSV
        $p = $this->db->findById($id);

        // Si no existe, devolvemos error 404
        if (!$p) {
            return $this->fail(404, "Elemento no encontrado (id=$id)");
        }

        // Si existe, devolvemos el producto
        return $this->ok(200, 'Producto encontrado', $p);
    }

    /**
     * POST /public/producto/create
     * Crea un nuevo producto en el CSV
     */
    public function createProducto($data): array
    {
        // El body debe venir en formato array (JSON)
        if (!is_array($data)) {
            return $this->fail(400, 'Body JSON inválido');
        }

        // Campos mínimos obligatorios
        if (!isset($data['id'])) {
            return $this->fail(400, "Falta el campo 'id'");
        }

        if (!isset($data['nombre'])) {
            return $this->fail(400, "Falta el campo 'nombre'");
        }

        // Intentamos añadir el producto al CSV
        $ok = $this->db->appendIfIdNotExists($data);

        // Si no se puede crear (id duplicado o inválido)
        if (!$ok) {
            return $this->fail(409, 'No se ha podido crear (id duplicado o inválido)');
        }

        // Devolvemos el producto recién creado
        return $this->ok(
            201,
            'Producto creado',
            $this->db->findById((int)$data['id'])
        );
    }

    /**
     * PUT /public/producto/update/{id}
     * Actualiza un producto existente por su ID
     *
     * Ejercicio 5:
     * Si el ID no existe → devolver error 404
     */
    public function updateProducto($id, $data): array
    {
        $id = (int)$id;

        // El body debe ser un array
        if (!is_array($data)) {
            return $this->fail(400, 'Body JSON inválido');
        }

        // Intentamos actualizar el producto
        $updated = $this->db->updateById($id, $data);

        // Si no se ha actualizado, el ID no existe
        if (!$updated) {
            return $this->fail(
                404,
                "Elemento no encontrado: no se puede actualizar (id=$id)"
            );
        }

        // Devolvemos el producto actualizado
        return $this->ok(
            200,
            'Producto actualizado',
            $this->db->findById($id)
        );
    }

    /**
     * DELETE /public/producto/delete/{id}
     * Elimina un producto por su ID
     */
    public function deleteProducto($id): array
    {
        $id = (int)$id;

        // Intentamos borrar el producto
        $deleted = $this->db->deleteById($id);

        // Si no se ha podido borrar, el ID no existe
        if (!$deleted) {
            return $this->fail(
                404,
                "Elemento no encontrado: no se puede borrar (id=$id)"
            );
        }

        // Confirmación de borrado
        return $this->ok(200, 'Producto borrado');
    }
}
