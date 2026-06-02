<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/doctree.php - Documentation tree viewer
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

global $DB, $CFG_GLPI;

$canWrite = Session::haveRight('plugin_ciscontrols', UPDATE);

// Active folder from GET
$activeFolderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : 0;

// Load all docs/folders
$allItems = [];
$rows = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_documents', 'ORDER' => 'is_folder DESC, name ASC']);
foreach ($rows as $r) {
    $allItems[$r['id']] = $r;
}

// Build tree (parent_id => [children ids])
$children = [];
foreach ($allItems as $id => $item) {
    $pid = $item['parent_id'] ?? null;
    $children[$pid][] = $id;
}

// Load all controls for linking modal
$allControls = [];
$ctrlRows = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls', 'WHERE' => ['is_active' => 1], 'ORDER' => 'name ASC']);
foreach ($ctrlRows as $c) {
    $allControls[] = $c;
}

// File icon helper
function getFileIcon(string $mime): string {
    if (str_contains($mime, 'pdf')) return 'ti ti-file-type-pdf text-danger';
    if (str_contains($mime, 'image')) return 'ti ti-photo text-success';
    if (str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') || str_contains($mime, 'csv')) return 'ti ti-file-spreadsheet text-success';
    if (str_contains($mime, 'word') || str_contains($mime, 'document')) return 'ti ti-file-word text-primary';
    if (str_contains($mime, 'zip') || str_contains($mime, 'rar') || str_contains($mime, '7z') || str_contains($mime, 'compressed')) return 'ti ti-file-zip text-warning';
    if (str_contains($mime, 'text')) return 'ti ti-file-text text-muted';
    return 'ti ti-file text-muted';
}

function formatSize(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes/1024, 1) . ' KB';
    return round($bytes/1048576, 1) . ' MB';
}

// Render tree node recursively
function renderTreeNode(array $allItems, array $children, ?int $parentId, int $depth, int $activeFolderId, string $rootDoc): void {
    if (!isset($children[$parentId])) return;
    foreach ($children[$parentId] as $id) {
        $item = $allItems[$id];
        if (!$item['is_folder']) continue;
        $hasChildren = isset($children[$id]) && !empty(array_filter($children[$id], fn($cid) => $allItems[$cid]['is_folder']));
        $collapseId = 'folder_collapse_' . $id;
        $isActive = ($id === $activeFolderId);
        $activeClass = $isActive ? ' active fw-bold' : '';
        echo "<li class='list-group-item list-group-item-action py-1 px-2 border-0" . $activeClass . "' style='padding-left:" . ($depth * 16 + 8) . "px;'>";
        if ($hasChildren) {
            echo "<button class='btn btn-link btn-sm p-0 me-1 text-muted' data-bs-toggle='collapse' data-bs-target='#" . $collapseId . "'><i class='ti ti-chevron-right'></i></button>";
        } else {
            echo "<span class='me-1' style='width:20px;display:inline-block;'></span>";
        }
        echo "<a href='" . $rootDoc . "/plugins/ciscontrols/front/doctree.php?folder_id=" . $id . "' class='text-decoration-none text-dark'>";
        echo "<i class='ti ti-folder" . ($isActive ? '-open' : '') . " text-warning me-1'></i>";
        echo htmlspecialchars($item['name']);
        echo "</a>";
        echo "</li>";
        if ($hasChildren) {
            echo "<div class='collapse" . ($isActive ? ' show' : '') . "' id='" . $collapseId . "'>";
            echo "<ul class='list-group list-group-flush'>";
            renderTreeNode($allItems, $children, $id, $depth + 1, $activeFolderId, $rootDoc);
            echo "</ul></div>";
        }
    }
}

Html::header('CIS Controls - Documentación', $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');
?>
<div class="container-fluid mt-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="ti ti-folder-open me-2"></i>Árbol de Documentación</h2>
    <?php if ($canWrite): ?>
    <div>
      <button class="btn btn-outline-primary btn-sm me-2" onclick="document.getElementById('newFolderModal').querySelector('[name=parent_id]').value='<?= $activeFolderId ?>'; new bootstrap.Modal(document.getElementById('newFolderModal')).show();">
        <i class="ti ti-folder-plus me-1"></i>Nueva Carpeta
      </button>
      <?php if ($activeFolderId > 0): ?>
      <button class="btn btn-primary btn-sm" onclick="new bootstrap.Modal(document.getElementById('uploadModal')).show();">
        <i class="ti ti-upload me-1"></i>Subir Archivo
      </button>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="row g-3">
    <!-- Left panel: folder tree -->
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-header py-2">
          <strong><i class="ti ti-sitemap me-1"></i>Carpetas</strong>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <li class="list-group-item list-group-item-action py-1 px-2 border-0 <?= $activeFolderId === 0 ? 'active fw-bold' : '' ?>">
              <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.php" class="text-decoration-none text-dark">
                <i class="ti ti-home text-secondary me-1"></i>Raíz
              </a>
            </li>
            <?php renderTreeNode($allItems, $children, null, 0, $activeFolderId, $CFG_GLPI['root_doc']); ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- Right panel: folder contents -->
    <div class="col-md-9">
      <?php
      $currentFolder = $activeFolderId > 0 ? ($allItems[$activeFolderId] ?? null) : null;
      $folderName = $currentFolder ? $currentFolder['name'] : 'Raíz';
      $folderContents = $children[$activeFolderId > 0 ? $activeFolderId : null] ?? [];

      // Subfolders
      $subFolders = array_filter($folderContents, fn($id) => $allItems[$id]['is_folder']);
      // Files
      $files = array_filter($folderContents, fn($id) => !$allItems[$id]['is_folder']);
      ?>
      <div class="card shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <strong>
            <i class="ti ti-folder-open text-warning me-1"></i><?= htmlspecialchars($folderName) ?>
          </strong>
          <small class="text-muted"><?= count($files) ?> archivo(s), <?= count($subFolders) ?> carpeta(s)</small>
        </div>
        <div class="card-body">
          <?php if (!empty($subFolders)): ?>
          <h6 class="text-muted mb-2"><i class="ti ti-folder me-1"></i>Subcarpetas</h6>
          <div class="row g-2 mb-3">
            <?php foreach ($subFolders as $subId):
              $sub = $allItems[$subId];
              $subCount = count(array_filter($children[$subId] ?? [], fn($cid) => !$allItems[$cid]['is_folder']));
            ?>
            <div class="col-md-4 col-sm-6">
              <div class="card border-0 bg-light h-100">
                <div class="card-body py-2 px-3 d-flex align-items-center">
                  <i class="ti ti-folder text-warning me-2 fs-5"></i>
                  <div class="flex-grow-1 overflow-hidden">
                    <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.php?folder_id=<?= $subId ?>" class="text-decoration-none text-dark fw-bold text-truncate d-block">
                      <?= htmlspecialchars($sub['name']) ?>
                    </a>
                    <small class="text-muted"><?= $subCount ?> archivos</small>
                  </div>
                  <?php if ($canWrite): ?>
                  <form method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.form.php" class="d-inline"
                        onsubmit="return confirm('¿Eliminar carpeta? Solo se pueden eliminar carpetas vacías.')">
                    <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $subId ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars('?folder_id='.$activeFolderId) ?>">
                    <button type="submit" class="btn btn-sm btn-link text-danger ms-1" title="Eliminar carpeta">
                      <i class="ti ti-trash"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($files)): ?>
          <h6 class="text-muted mb-2"><i class="ti ti-files me-1"></i>Archivos</h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Nombre</th>
                  <th>Tamaño</th>
                  <th>Fecha</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($files as $fileId):
                  $file = $allItems[$fileId];
                  $icon = getFileIcon($file['mime_type'] ?? '');
                  $isImage = str_contains($file['mime_type'] ?? '', 'image');
                  $isPdf = str_contains($file['mime_type'] ?? '', 'pdf');
                ?>
                <tr>
                  <td>
                    <i class="<?= $icon ?> me-2 fs-5"></i>
                    <?php if ($isImage || $isPdf): ?>
                      <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.form.php?action=download&id=<?= $fileId ?>"
                         target="<?= $isPdf ? '_blank' : '_self' ?>" class="text-decoration-none">
                        <?= htmlspecialchars($file['original_name'] ?? $file['name']) ?>
                      </a>
                    <?php else: ?>
                      <?= htmlspecialchars($file['original_name'] ?? $file['name']) ?>
                    <?php endif; ?>
                  </td>
                  <td><small class="text-muted"><?= $file['file_size'] ? formatSize((int)$file['file_size']) : '-' ?></small></td>
                  <td><small class="text-muted"><?= $file['date_creation'] ? date('d/m/Y', strtotime($file['date_creation'])) : '-' ?></small></td>
                  <td>
                    <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.form.php?action=download&id=<?= $fileId ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="Descargar">
                      <i class="ti ti-download"></i>
                    </a>
                    <?php if ($canWrite): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary me-1" title="Vincular a control"
                      onclick="openLinkDocModal(<?= $fileId ?>, '<?= addslashes(htmlspecialchars($file['original_name'] ?? $file['name'])) ?>')">
                      <i class="ti ti-link"></i>
                    </button>
                    <form method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.form.php" class="d-inline"
                          onsubmit="return confirm('¿Eliminar este archivo?')">
                      <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $fileId ?>">
                      <input type="hidden" name="redirect" value="<?= htmlspecialchars('?folder_id='.$activeFolderId) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                        <i class="ti ti-trash"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php elseif (empty($subFolders)): ?>
          <div class="text-center text-muted py-4">
            <i class="ti ti-folder-open fs-1 mb-2 d-block"></i>
            <p>Esta carpeta está vacía.</p>
            <?php if ($canWrite && $activeFolderId > 0): ?>
            <button class="btn btn-sm btn-primary" onclick="new bootstrap.Modal(document.getElementById('uploadModal')).show();">
              <i class="ti ti-upload me-1"></i>Subir Archivo
            </button>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- New Folder Modal -->
<?php if ($canWrite): ?>
<div class="modal fade" id="newFolderModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-folder-plus me-2"></i>Nueva Carpeta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.form.php">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
        <input type="hidden" name="action" value="new_folder">
        <input type="hidden" name="parent_id" value="<?= $activeFolderId ?>">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars('?folder_id='.$activeFolderId) ?>">
        <div class="modal-body">
          <label class="form-label">Nombre de la carpeta</label>
          <input type="text" name="folder_name" class="form-control" required placeholder="Ej: Procedimientos 2025">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Crear</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-upload me-2"></i>Subir Archivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.form.php" enctype="multipart/form-data">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="folder_id" value="<?= $activeFolderId ?>">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars('?folder_id='.$activeFolderId) ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Archivo</label>
            <input type="file" name="upload_file" class="form-control" required
              accept=".jpg,.jpeg,.png,.pdf,.txt,.zip,.rar,.7z,.xls,.xlsx,.doc,.docx,.csv">
            <small class="text-muted">Tipos permitidos: jpg, jpeg, png, pdf, txt, zip, rar, 7z, xls, xlsx, doc, docx, csv</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción (opcional)</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-upload me-1"></i>Subir</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Link Document to Control Modal -->
<div class="modal fade" id="linkDocModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-link me-2"></i>Vincular a Control</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.form.php">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
        <input type="hidden" name="action" value="link_control">
        <input type="hidden" name="doc_id" id="modal_doc_id" value="">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars('?folder_id='.$activeFolderId) ?>">
        <div class="modal-body">
          <p class="text-muted small mb-3">Archivo: <strong id="modal_doc_name"></strong></p>
          <div class="mb-3">
            <label class="form-label">Control a vincular</label>
            <select name="control_id" class="form-select" required>
              <option value="">-- Seleccionar control --</option>
              <?php foreach ($allControls as $ctrl): ?>
              <option value="<?= $ctrl['id'] ?>"><?= htmlspecialchars($ctrl['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipo de documento</label>
            <select name="link_type" class="form-select">
              <option value="procedimiento">Procedimiento</option>
              <option value="registro">Registro</option>
              <option value="evidencia">Evidencia</option>
              <option value="politica">Política</option>
              <option value="otro">Otro</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Vincular</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function openLinkDocModal(docId, docName) {
    document.getElementById('modal_doc_id').value = docId;
    document.getElementById('modal_doc_name').textContent = docName;
    var modal = new bootstrap.Modal(document.getElementById('linkDocModal'));
    modal.show();
}
</script>
<?php Html::footer(); ?>
