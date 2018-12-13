<ul class="menu">
  <li <?php if ('connections' === $activeItem): ?>class="active"<?php endif; ?>><a href="connections"><?=$this->t('Connections'); ?></a></li>
  <li <?php if ('users' === $activeItem): ?>class="active"<?php endif; ?>>      <a href="users"><?=$this->t('Users'); ?></a></li>
  <li <?php if ('info' === $activeItem): ?>class="active"<?php endif; ?>>       <a href="info"><?=$this->t('Info'); ?></a></li>
  <li <?php if ('stats' === $activeItem): ?>class="active"<?php endif; ?>>      <a href="stats"><?=$this->t('Stats'); ?></a></li>
  <li <?php if ('messages' === $activeItem): ?>class="active"<?php endif; ?>>   <a href="messages"><?=$this->t('Messages'); ?></a></li>
  <li <?php if ('log' === $activeItem): ?>class="active"<?php endif; ?>>        <a href="log"><?=$this->t('Log'); ?></a></li>
</ul>
