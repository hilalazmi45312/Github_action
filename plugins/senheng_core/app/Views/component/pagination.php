   <style>
       .tablenav-pages {
           margin-top: 20px;
           text-align: right;
       }

       .tablenav-pages .pagination-links {
           display: inline-block;
       }

       .tablenav-pages .button,
       .tablenav-pages .current-page {
           display: inline-block;
           padding: 6px 12px;
           margin: 0 2px;
           background: #f6f7f7;
           border: 1px solid #ccd0d4;
           color: #0073aa;
           text-decoration: none;
           border-radius: 3px;
           font-size: 13px;
           min-width: 32px;
           text-align: center;
       }

       .tablenav-pages .button:hover {
           background: #f0f0f0;
           border-color: #999;
           color: #000;
       }

       .tablenav-pages .current-page {
           font-weight: bold;
           background: #0073aa;
           color: #fff;
           border-color: #0073aa;
       }
   </style>
   <!-- Pagination -->
   <div class="tablenav-pages">
       <span class="pagination-links">

           <?php if ($currentPage > 1): ?>
               <a class="button" href="<?= $base_url . '&paged=' . ($currentPage - 1) ?>">&laquo;</a>
           <?php endif; ?>

           <?php for ($i = 1; $i <= $lastPage; $i++): ?>
               <a class="<?= $i === $currentPage ? 'current-page button' : 'button' ?>"
                   href="<?= $base_url . '&paged=' . $i ?>">
                   <?= $i ?>
               </a>
           <?php endfor; ?>

           <?php if ($currentPage < $lastPage): ?>
               <a class="button" href="<?= $base_url . '&paged=' . ($currentPage + 1) ?>">&raquo;</a>
           <?php endif; ?>

       </span>
   </div>