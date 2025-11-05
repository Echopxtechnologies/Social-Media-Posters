<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-dashboard"></i> <?php echo $title; ?>
                        </h4>
                        <hr class="hr-panel-heading" />

                        <!-- Stats Cards -->
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s" style="border-left: 4px solid #4267B2;">
                                    <div class="panel-body">
                                        <h3 class="no-margin"><?php echo $stats['active_connections']; ?></h3>
                                        <p class="text-muted">Active Connections</p>
                                        <a href="<?php echo admin_url('sm_posters/connections'); ?>" class="btn btn-sm btn-default">
                                            Manage
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s" style="border-left: 4px solid #5cb85c;">
                                    <div class="panel-body">
                                        <h3 class="no-margin"><?php echo $stats['published_posts']; ?></h3>
                                        <p class="text-muted">Published Posts</p>
                                        <a href="<?php echo admin_url('sm_posters/posts'); ?>" class="btn btn-sm btn-default">
                                            View All
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s" style="border-left: 4px solid #5bc0de;">
                                    <div class="panel-body">
                                        <h3 class="no-margin"><?php echo $stats['scheduled_posts']; ?></h3>
                                        <p class="text-muted">Scheduled Posts</p>
                                        <a href="<?php echo admin_url('sm_posters/posts'); ?>" class="btn btn-sm btn-default">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s" style="border-left: 4px solid #d9534f;">
                                    <div class="panel-body">
                                        <h3 class="no-margin"><?php echo $stats['failed_posts']; ?></h3>
                                        <p class="text-muted">Failed Posts</p>
                                        <?php if ($stats['failed_posts'] > 0) { ?>
                                        <a href="<?php echo admin_url('sm_posters/posts'); ?>" class="btn btn-sm btn-danger">
                                            Review
                                        </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h4><i class="fa fa-bolt"></i> Quick Actions</h4>
                                        <hr>
                                        <a href="<?php echo admin_url('sm_posters/create_post'); ?>" class="btn btn-lg btn-primary">
                                            <i class="fa fa-plus"></i> Create New Post
                                        </a>
                                        <a href="<?php echo admin_url('sm_posters/connections'); ?>" class="btn btn-lg btn-default">
                                            <i class="fa fa-plug"></i> Manage Connections
                                        </a>
                                        <a href="<?php echo admin_url('sm_posters/posts'); ?>" class="btn btn-lg btn-default">
                                            <i class="fa fa-history"></i> View Posts History
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Platform Stats -->
                        <?php if (!empty($stats['platforms'])) { ?>
                        <div class="row mtop15">
                            <div class="col-md-6">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h4><i class="fa fa-share-alt"></i> Connected Platforms</h4>
                                        <hr>
                                        <div class="row">
                                            <?php 
                                            $platform_info = [
                                                'facebook' => ['name' => 'Facebook', 'icon' => 'fa-facebook', 'color' => '#4267B2'],
                                                'instagram' => ['name' => 'Instagram', 'icon' => 'fa-instagram', 'color' => '#E4405F'],
                                                'x' => ['name' => 'X', 'icon' => 'fa-twitter', 'color' => '#000000'],
                                                'linkedin' => ['name' => 'LinkedIn', 'icon' => 'fa-linkedin', 'color' => '#0077B5'],
                                                'tumblr' => ['name' => 'Tumblr', 'icon' => 'fa-tumblr', 'color' => '#35465C'],
                                                'pinterest' => ['name' => 'Pinterest', 'icon' => 'fa-pinterest', 'color' => '#BD081C']
                                            ];
                                            
                                            foreach ($stats['platforms'] as $platform => $count) {
                                                $info = isset($platform_info[$platform]) ? $platform_info[$platform] : ['name' => ucfirst($platform), 'icon' => 'fa-share', 'color' => '#999'];
                                            ?>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="well text-center" style="border-left: 4px solid <?php echo $info['color']; ?>;">
                                                    <i class="fa <?php echo $info['icon']; ?> fa-2x" style="color: <?php echo $info['color']; ?>;"></i>
                                                    <h3><?php echo $count; ?></h3>
                                                    <p><?php echo $info['name']; ?></p>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Posts -->
                            <div class="col-md-6">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h4><i class="fa fa-clock-o"></i> Recent Posts</h4>
                                        <hr>
                                        <?php if (!empty($stats['recent_posts'])) { ?>
                                            <ul class="list-unstyled">
                                                <?php foreach ($stats['recent_posts'] as $post) { 
                                                    // Custom character limiter
                                                    $message_preview = $post->message;
                                                    if (strlen($message_preview) > 50) {
                                                        $message_preview = substr($message_preview, 0, 50) . '...';
                                                    }
                                                    
                                                    // Custom time ago
                                                    $time_ago = '';
                                                    $seconds = strtotime('now') - strtotime($post->created_at);
                                                    if ($seconds < 60) {
                                                        $time_ago = $seconds . ' seconds ago';
                                                    } elseif ($seconds < 3600) {
                                                        $time_ago = floor($seconds / 60) . ' minutes ago';
                                                    } elseif ($seconds < 86400) {
                                                        $time_ago = floor($seconds / 3600) . ' hours ago';
                                                    } else {
                                                        $time_ago = floor($seconds / 86400) . ' days ago';
                                                    }
                                                ?>
                                                <li class="mtop10">
                                                    <div style="border-left: 3px solid #ddd; padding-left: 10px;">
                                                        <strong><?php echo htmlspecialchars($message_preview); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fa fa-clock-o"></i> <?php echo $time_ago; ?>
                                                            <span class="label label-<?php echo $post->status == 'published' ? 'success' : 'default'; ?>">
                                                                <?php echo ucfirst($post->status); ?>
                                                            </span>
                                                        </small>
                                                    </div>
                                                </li>
                                                <?php } ?>
                                            </ul>
                                        <?php } else { ?>
                                            <p class="text-muted text-center">No recent posts</p>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>