<?php

require_once __DIR__ . '/../utils/CsvDataHandler.php';
require_once __DIR__ . '/../models/Producto.php';

class ProductoController {

    private CsvDataHandler $db;

    public function __construct()
    {
        $csvFile = __DIR__ . '/../../data/productos.csv';
        $this->db = new CsvDataHandler($csvFile);
    }

    // GET ALL
    public function getAllProductos()
    {
        echo "Hola desde el metodo getAllProductos() de PRODUCTO Controller <br>";

        $productos = $this->db->readAllAsArrays();

        echo "<pre>";
        print_r($productos);
        echo "</pre>";
    }

    // GET BY ID
    public function getProductoById($id)
    {
        echo "Hola desde el metodo getProductoById(" . $id . ") de PRODUCTO Controller <br>";

        $producto = $this->db->findById((int)$id);

        if ($producto === null) {
            echo "❌ Producto no encontrado con id=" . $id . "<br>";
            return;
        }

        echo "<pre>";
        print_r($producto);
        echo "</pre>";
    }

    // POST (ID lo pone el usuario en el body)
    public function createProducto($data)
    {
        echo "Hola desde el metodo createProducto() de PRODUCTO Controller <br>";
        echo "Los datos del PRODUCTO son " . json_encode($data) . "<br>";

        if (!is_array($data) || !isset($data['id'])) {
            echo "❌ ERROR: debes enviar un campo 'id' en el body<br>";
            return;
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

        $ok = $this->db->appendIfIdNotExists($producto);

        if (!$ok) {
            echo "❌ ERROR: id duplicado o inválido (id=" . $producto['id'] . ")<br>";
            return;
        }

        echo "✅ Producto añadido al CSV como JSON en una línea. ID=" . $producto['id'] . "<br>";
        echo "<pre>";
        print_r($producto);
        echo "</pre>";
    }

    // PUT (actualiza por ID de la URL; si viene id en body, debe coincidir)
    public function updateProducto($id, $data)
    {
        echo "Hola desde el metodo updateProducto() de PRODUCTO Controller <br>";
        echo "El ID del producto es " . $id . "<br>";
        echo "Los datos del PRODUCTO son " . json_encode($data) . "<br>";

        $idUrl = (int)$id;

        if (!is_array($data)) {
            $data = [];
        }

        // Si el body trae id, lo validamos
        if (isset($data['id']) && (int)$data['id'] !== $idUrl) {
            echo "❌ ERROR: el 'id' del body (" . (int)$data['id'] . ") no coincide con el id de la URL ($idUrl)<br>";
            return;
        }

        // (aunque venga, no dejamos que cambie el id)
        unset($data['id']);

        $ok = $this->db->updateById($idUrl, $data);

        if (!$ok) {
            echo "❌ No se puede actualizar: producto no existe (id=" . $idUrl . ")<br>";
            return;
        }

        $updated = $this->db->findById($idUrl);

        echo "✅ Producto actualizado (id=" . $idUrl . ") y CSV reescrito<br>";
        echo "<pre>";
        print_r($updated);
        echo "</pre>";
    }

    // DELETE (borra por ID de la URL)
    public function deleteProducto($id)
    {
        echo "Hola desde el metodo deleteProducto() de PRODUCTO Controller <br>";
        echo "El ID del producto a borrar es " . $id . "<br>";

        $idUrl = (int)$id;

        $ok = $this->db->deleteById($idUrl);

        if (!$ok) {
            echo "❌ No se puede borrar: producto no existe (id=" . $idUrl . ")<br>";
            return;
        }

        echo "✅ Producto borrado (id=" . $idUrl . ") y CSV reescrito<br>";
    }
}
