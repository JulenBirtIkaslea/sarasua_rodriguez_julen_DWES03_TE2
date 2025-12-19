<?php

require_once __DIR__ . '/../utils/CsvDataHandler.php';

class ProductoController
{
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
            'message' => $message,
        ];
        if ($data !== null) $resp['data'] = $data;
        return $resp;
    }

    private function fail(int $code, string $message, $data = null): array
    {
        $resp = [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
        ];
        if ($data !== null) $resp['data'] = $data;
        return $resp;
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') return [];

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    // GET /public/producto/get
    public function getAllProductos(): array
    {
        $items = $this->db->readAllAsArrays();
        return $this->ok(200, 'Listado de productos', $items);
    }

    // GET /public/producto/get/{id}
    public function getProductoById($id): array
    {
        $id = (int)$id;
        if ($id <= 0) return $this->fail(400, 'ID inválido');

        $item = $this->db->findById($id);
        if ($item === null) return $this->fail(404, "Producto no encontrado (id=$id)");

        return $this->ok(200, 'Producto encontrado', $item);
    }

    // POST /public/producto/create
    public function createProducto(): array
    {
        $data = $this->readJsonBody();

        if (!isset($data['id'])) return $this->fail(400, 'Falta el campo id');

        $data['id'] = (int)$data['id'];
        if ($data['id'] <= 0) return $this->fail(400, 'ID inválido');

        if (!isset($data['nombre']) || trim((string)$data['nombre']) === '') {
            return $this->fail(400, 'Falta el campo nombre');
        }

        $ok = $this->db->appendIfIdNotExists($data);

        if (!$ok) {
            return $this->fail(409, "No se ha podido crear: id duplicado o inválido (id={$data['id']})");
        }

        $created = $this->db->findById($data['id']);
        return $this->ok(201, 'Producto creado', $created);
    }

    // PUT /public/producto/update/{id}
    // Ejercicio 5: si el ID no existe -> 404 con mensaje de error
    public function updateProducto($id): array
    {
        $id = (int)$id;
        if ($id <= 0) return $this->fail(400, 'ID inválido');

        $data = $this->readJsonBody();
        if (empty($data)) {
            return $this->fail(400, 'Body JSON vacío o inválido');
        }

        $ok = $this->db->updateById($id, $data);

        if (!$ok) {
            // ✅ Elemento no encontrado
            return $this->fail(404, "No se puede actualizar: producto no encontrado (id=$id)");
        }

        $updated = $this->db->findById($id);
        return $this->ok(200, 'Producto actualizado', $updated);
    }

    // DELETE /public/producto/delete/{id}
    public function deleteProducto($id): array
    {
        $id = (int)$id;
        if ($id <= 0) return $this->fail(400, 'ID inválido');

        $existing = $this->db->findById($id);
        if ($existing === null) return $this->fail(404, "Producto no encontrado (id=$id)");

        $ok = $this->db->deleteById($id);
        if (!$ok) return $this->fail(500, 'No se ha podido borrar el producto');

        return $this->ok(200, 'Producto borrado', $existing);
    }
}
