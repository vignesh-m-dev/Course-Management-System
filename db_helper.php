<?php
/**
 * Delete record and reset AUTO_INCREMENT
 * @param $conn - Database connection
 * @param $table - Table name
 * @param $idColumn - ID column name (e.g., 'id', 'user_id', 'cart_id')
 * @param $id - ID value to delete
 */
function deleteAndResetAutoIncrement($conn, $table, $idColumn, $id) {
    $id = intval($id);
    
    // Delete the record
    $conn->query("DELETE FROM $table WHERE $idColumn = $id");
    
    // Reset AUTO_INCREMENT to next available ID
    $maxIdResult = $conn->query("SELECT MAX($idColumn) AS max_id FROM $table");
    $maxIdRow = $maxIdResult->fetch_assoc();
    $nextId = ($maxIdRow['max_id'] ?? 0) + 1;
    
    // Reset AUTO_INCREMENT
    $conn->query("ALTER TABLE $table AUTO_INCREMENT = $nextId");
    
    return true;
}

/**
 * Calculate discounted price based on deal string
 * @param $originalPrice - Original product price
 * @param $dealString - Deal string (e.g., "20% OFF")
 * @return float - Discounted price
 */
function getDiscountedPrice($originalPrice, $dealString) {
    if (empty($dealString)) {
        return $originalPrice;
    }
    
    // Extract percentage from deal string (e.g., "20% OFF" -> 20)
    preg_match('/(\d+)%/', $dealString, $matches);
    
    if (!empty($matches[1])) {
        $discountPercent = (int)$matches[1];
        return round($originalPrice * (1 - $discountPercent / 100), 2);
    }
    
    return $originalPrice;
}

/**
 * Get discount percentage from deal string
 * @param $dealString - Deal string (e.g., "20% OFF")
 * @return int - Discount percentage
 */
function getDiscountPercent($dealString) {
    if (empty($dealString)) {
        return 0;
    }
    
    preg_match('/(\d+)%/', $dealString, $matches);
    return !empty($matches[1]) ? (int)$matches[1] : 0;
}
?>
