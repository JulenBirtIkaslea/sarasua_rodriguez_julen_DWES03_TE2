<?php

require_once __DIR__ . '/../utils/CsvDataHandler.php';
require_once __DIR__ . '/../models/Producto.php'; // (puede quedarse, aunque no lo uses)

class ProductoController {

    private CsvDataHandler $db;

    public function __construct()
    {
        $csvFile = __DIR__ . '/../../data/productos.csv';
        $this->db = new CsvDataHandler($csvFile);
    }

    private function ok(int $code, string $message, $data = null): array
    {
        $resp = [
            'status' => 'success',
            'code' => $code,
            'message' => $message
        ];
        if ($data !== null) $resp['data'] = $data;
        return $resp;
    }

    private function fail(int $code, string $message, $data = null): array
    {
        $resp = [
            'status' => 'error',
            'code' => $code,
            'message' => $message
        ];
        if ($data !== null) $resp['data'] = $data;
        return $resp;
    }

    // GET ALL
    public function getAllProductos(): array
    {
        $productos = $this->db->readAllAsArrays();
        return $this->ok(200, 'Listado de productos', $productos);
    }

    // GET BY ID
    public function getProductoById($id): array
    {
        $id = (int)$id;
        if ($id <= 0) return $this->fail(400, 'ID inválido');

        $producto = $this->db->findById($id);

        if ($producto === null) {
            return $this->fail(404, "Elemento no encontrado: producto no existe (id=$id)");
        }

        return $this->ok(200, 'Producto encontrado', $producto);
    }

    // POST (ID lo pone el usuario en el body)
    public function createProducto($data): array
    {
        if (!is_array($data)) {
            return $this->fail(400, 'Body JSON inválido');
        }

        if (!isset($data['id'])) {
            return $this->fail(400, "Falta el campo 'id'");
        }

        $producto = [
            'id' => (int)$data['id'],
            'nombre' => (string)($data['nombre'] ?? ''),
            'categoria' => (string)($data['categoria'] ?? ''),
            'talla' => (string)($data['talla'] ?? ''),
            'color' => (string)($data['color'] ?? ''),
            'precio' => isset($data['precio']) ? (float)$data['precio'] : 0.0,
            'stock' => isset($data['stock']) ? (int)$data['stock'] : 0
        ];

        if ($producto['id'] <= 0) return $this->fail(400, 'ID inválido');
        if (trim($producto['nombre']) === '') return $this->fail(400, "Falta el campo 'nombre'");

        $ok = $this->db->appendIfIdNotExists($producto);

        if (!$ok) {
            return $this->fail(409, "No se ha podido crear: id duplicado o inválido (id={$producto['id']})");
        }

        $created = $this->db->findById($producto['id']);
        return $this->ok(201, 'Producto creado', $created);
    }

    // PUT (actualiza por ID de la URL; si viene id en body, debe coincidir)
    // Ejercicio 5: ID inexistente -> 404 JSON
    public function updateProducto($id, $data): array
    {
        $idUrl = (int)$id;
        if ($idUrl <= 0) return $this->fail(400, 'ID inválido');

        if (!is_array($data)) {
            return $this->fail(400, 'Body JSON inválido');
        }

        if (isset($data['id']) && (int)$data['id'] !== $idUrl) {
            return $this->fail(400, "El 'id' del body no coincide con el id de la URL (url=$idUrl)");
        }

        unset($data['id']); // no permitir cambiar id

        $ok = $this->db->updateById($idUrl, $data);

        if (!$ok) {
            // ✅ Elemento no encontrado (Ejercicio 5)
            return $this->fail(404, "Elemento no encontrado: no se puede actualizar (id=$idUrl)");
        }

        $updated = $this->db->findById($idUrl);
        return $this->ok(200, 'Producto actualizado', $updated);
    }

    // DELETE
    public function deleteProducto($id): array
    {
        $idUrl = (int)$id;
        if ($idUrl <= 0) return $this->fail(400, 'ID inválido');

        $existing = $this->db->findById($idUrl);
        if ($existing === null) {
            return $this->fail(404, "Elemento no encontrado: no se puede borrar (id=$idUrl)");
        }

        $ok = $this->db->deleteById($idUrl);

        if (!$ok) {
            return $this->fail(500, 'No se ha podido borrar el producto');
        }

        return $this->ok(200, 'Producto borrado', $existing);
    }
}
