<?php
include 'includes/db.php';

$id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? 'maintenance';

if (empty($id)) {
    echo "<tr><td colspan='6' style='text-align:center;'>Error: No ID provided.</td></tr>";
    exit;
}

if ($type === 'maintenance' || $type === 'retired') {
    $colSpan = ($type === 'retired') ? 4 : 6;
    $foundData = false;

    // 1. Try fetching from UNIT_HISTORY
    $stmt_unit = $conn->prepare("SELECT report_date, report_actor, report_affected, report_action, report_remarks, report_status FROM unit_history WHERE set_id = ? ORDER BY report_date DESC");
    $stmt_unit->bind_param("s", $id);
    $stmt_unit->execute();
    $res_unit = $stmt_unit->get_result();

    if ($res_unit->num_rows > 0) {
        $foundData = true;
        while ($row = $res_unit->fetch_assoc()) {
            $badgeClass = ($row['report_status'] == 'Resolved') ? 'green' : (($row['report_status'] == 'Condemned') ? 'red' : 'orange');
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars(date('m/d/Y', strtotime($row['report_date']))) . "</td>";
            echo "<td>" . htmlspecialchars($row['report_actor']) . "</td>";
            
            if ($type === 'maintenance') {
                echo "<td>" . htmlspecialchars($row['report_affected']) . "</td>";
                echo "<td>" . htmlspecialchars($row['report_action']) . "</td>";
            }
            
            echo "<td>" . htmlspecialchars($row['report_remarks']) . "</td>";
            echo "<td><span class='badge {$badgeClass}'>" . htmlspecialchars($row['report_status']) . "</span></td>";
            echo "</tr>";
        }
    }

    // 2. If not found in units, try ASSET_HISTORY
    if (!$foundData) {
        $stmt_asset = $conn->prepare("SELECT report_date, report_actor, report_remarks, report_status FROM asset_history WHERE asset_id = ? ORDER BY report_date DESC");
        $stmt_asset->bind_param("s", $id);
        $stmt_asset->execute();
        $res_asset = $stmt_asset->get_result();

        if ($res_asset->num_rows > 0) {
            $foundData = true;
            while ($row = $res_asset->fetch_assoc()) {
                $badgeClass = ($row['report_status'] == 'Resolved') ? 'green' : (($row['report_status'] == 'Condemned') ? 'red' : 'orange');
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars(date('m/d/Y', strtotime($row['report_date']))) . "</td>";
                echo "<td>" . htmlspecialchars($row['report_actor']) . "</td>";
                
                if ($type === 'maintenance') {
                    echo "<td>-</td>";
                    echo "<td>-</td>";
                }
                
                echo "<td>" . htmlspecialchars($row['report_remarks']) . "</td>";
                echo "<td><span class='badge {$badgeClass}'>" . htmlspecialchars($row['report_status']) . "</span></td>";
                echo "</tr>";
            }
        }
    }

    if (!$foundData) {
        echo "<tr><td colspan='{$colSpan}' style='text-align:center; padding: 20px; color: #757575;'>No history found for this item.</td></tr>";
    }

} elseif ($type === 'archive') {
    // 3. FETCH ARCHIVE DETAILS
    // Querying the laboratories table for the status
    $stmt = $conn->prepare("SELECT lab_status, lab_name FROM laboratories WHERE lab_room = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        echo json_encode([
            "status" => "success",
            "reason" => "This room is currently marked as: " . $row['lab_status'],
            "admin" => "System Record" // Placeholder since admin isn't in laboratories table
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "reason" => "No archive record found.",
            "admin" => "-"
        ]);
    }
}
?>