<?php
/**
 * Internal Messaging System
 * All users can send and receive messages
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db_operations.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$pageTitle = 'Messages';
$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            if ($action === 'send') {
                $receiverId = intval($_POST['receiver_id'] ?? 0);
                $subject = sanitize($_POST['subject'] ?? '');
                $content = sanitize($_POST['content'] ?? '');
                
                if ($receiverId && $subject && $content) {
                    sendMessage($userId, $receiverId, $subject, $content);
                    setFlash('success', 'Message sent successfully.');
                } else {
                    setFlash('error', 'Please fill in all fields.');
                }
            } elseif ($action === 'delete') {
                $messageId = intval($_POST['message_id'] ?? 0);
                $message = getMessageById($messageId);
                
                // Verify user owns this message
                if ($message && ($message['sender_id'] == $userId || $message['receiver_id'] == $userId)) {
                    deleteMessage($messageId);
                    setFlash('success', 'Message deleted.');
                }
            }
        } catch (Exception $e) {
            setFlash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Get messages
$view = $_GET['view'] ?? 'inbox';
$receivedMessages = getReceivedMessages($userId);
$sentMessages = getSentMessages($userId);

// Mark message as read if viewing
if (isset($_GET['read'])) {
    $messageId = intval($_GET['read']);
    $message = getMessageById($messageId);
    if ($message && $message['receiver_id'] == $userId) {
        markMessageAsRead($messageId);
    }
}

// Get users for compose
$teachers = getAllTeachers();
$students = getAllStudents();
$admins = getUsersByRole('admin');

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Messages</h1>
            <p class="text-muted mb-0">Send and receive messages</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
            <i class="bi bi-envelope-plus me-2"></i>Compose
        </button>
    </div>
    
    <?php displayFlash(); ?>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="list-group">
                <a href="?view=inbox" class="list-group-item list-group-item-action <?php echo $view === 'inbox' ? 'active' : ''; ?>">
                    <i class="bi bi-inbox me-2"></i>Inbox
                    <?php 
                    $unreadCount = getUnreadMessageCount($userId);
                    if ($unreadCount > 0): 
                    ?>
                        <span class="badge bg-danger float-end"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?view=sent" class="list-group-item list-group-item-action <?php echo $view === 'sent' ? 'active' : ''; ?>">
                    <i class="bi bi-send me-2"></i>Sent
                </a>
            </div>
        </div>
        
        <!-- Messages List -->
        <div class="col-md-9">
            <?php if (isset($_GET['read'])): 
                $viewMessage = getMessageById(intval($_GET['read']));
                if ($viewMessage && ($viewMessage['sender_id'] == $userId || $viewMessage['receiver_id'] == $userId)):
            ?>
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <a href="?view=<?php echo $view; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this message?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="message_id" value="<?php echo $viewMessage['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                    <div class="card-body">
                        <h4><?php echo sanitize($viewMessage['subject']); ?></h4>
                        <div class="text-muted mb-3">
                            <?php if ($viewMessage['sender_id'] == $userId): ?>
                                To: <?php echo sanitize($viewMessage['receiver_name']); ?>
                            <?php else: ?>
                                From: <?php echo sanitize($viewMessage['sender_name']); ?>
                            <?php endif; ?>
                            <span class="mx-2">|</span>
                            <?php echo formatDateTime($viewMessage['created_at']); ?>
                        </div>
                        <hr>
                        <div class="message-content">
                            <?php echo nl2br(sanitize($viewMessage['content'])); ?>
                        </div>
                        
                        <?php if ($viewMessage['receiver_id'] == $userId): ?>
                            <hr>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal">
                                <i class="bi bi-reply me-2"></i>Reply
                            </button>
                            
                            <!-- Reply Modal -->
                            <div class="modal fade" id="replyModal" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="send">
                                            <input type="hidden" name="receiver_id" value="<?php echo $viewMessage['sender_id']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reply to <?php echo sanitize($viewMessage['sender_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Subject</label>
                                                    <input type="text" class="form-control" name="subject" 
                                                           value="Re: <?php echo sanitize($viewMessage['subject']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Message</label>
                                                    <textarea class="form-control" name="content" rows="5" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Send Reply</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                endif;
            else: 
            ?>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $view === 'inbox' ? 'Inbox' : 'Sent Messages'; ?></h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php 
                        $messages = $view === 'inbox' ? $receivedMessages : $sentMessages;
                        if (empty($messages)): 
                        ?>
                            <div class="list-group-item text-muted text-center py-5">
                                <i class="bi bi-envelope-open display-4"></i>
                                <p class="mt-2 mb-0">No messages</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <a href="?view=<?php echo $view; ?>&read=<?php echo $msg['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo ($view === 'inbox' && !$msg['is_read']) ? 'fw-bold bg-light' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <?php if ($view === 'inbox'): ?>
                                                <?php if (!$msg['is_read']): ?>
                                                    <span class="badge bg-primary me-2">New</span>
                                                <?php endif; ?>
                                                <strong><?php echo sanitize($msg['sender_name']); ?></strong>
                                            <?php else: ?>
                                                To: <strong><?php echo sanitize($msg['receiver_name']); ?></strong>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo timeAgo($msg['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo sanitize($msg['subject']); ?></p>
                                    <small class="text-muted"><?php echo sanitize(substr($msg['content'], 0, 100)); ?>...</small>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Compose Modal -->
    <div class="modal fade" id="composeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="send">
                    <div class="modal-header">
                        <h5 class="modal-title">New Message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">To <span class="text-danger">*</span></label>
                            <select class="form-select" name="receiver_id" required>
                                <option value="">-- Select Recipient --</option>
                                <?php if ($userRole !== 'admin'): ?>
                                    <optgroup label="Administrators">
                                        <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>"><?php echo sanitize($admin['full_name']); ?> (Admin)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <?php if ($userRole !== 'teacher'): ?>
                                    <optgroup label="Teachers">
                                        <?php foreach ($teachers as $teacher): ?>
                                            <?php if ($teacher['id'] != $userId): ?>
                                                <option value="<?php echo $teacher['id']; ?>"><?php echo sanitize($teacher['full_name']); ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <?php if ($userRole === 'teacher' || $userRole === 'admin'): ?>
                                    <optgroup label="Students">
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?php echo $student['id']; ?>"><?php echo sanitize($student['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="content" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
