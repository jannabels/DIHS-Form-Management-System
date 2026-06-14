<?php
session_start();
require_once '../db_connect.php';

// [Previous PHP code remains the same until line 301]
                                    echo '    <div class="mt-1 w-2 h-2 rounded-full ' . $event['color'] . ' mr-3"></div>';
                                    echo '    <div>';
                                    echo '        <p class="text-sm font-medium text-gray-800">' . $event['title'] . '</p>';
                                    echo '        <p class="text-xs text-gray-500">' . $event['date'] . '</p>';
                                    echo '    </div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Calendar -->
                        <div class="bg-white p-4 rounded-xl shadow-sm">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-semibold text-gray-800">November 2025</h3>
                                <div class="flex space-x-1">
                                    <button class="p-1 rounded-full hover:bg-gray-100">
                                        <i class="fas fa-chevron-left text-gray-600 text-xs"></i>
                                    </button>
                                    <button class="p-1 rounded-full hover:bg-gray-100">
                                        <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-500 mb-1">
                                <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                <?php
                                $month = 11; $year = 2025;
                                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                                $firstDay = date('w', strtotime("$year-$month-01"));
                                
                                for ($i = 0; $i < $firstDay; $i++) {
                                    echo '<div class="h-6"></div>';
                                }
                                
                                $today = date('j');
                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $isToday = ($day == $today) ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'hover:bg-gray-100';
                                    echo "<div class='h-6 text-sm flex items-center justify-center rounded-full $isToday'>$day</div>";
                                }
                                ?>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <div class="text-center">
                                    <span class="text-sm font-medium text-gray-800">Today: November <?php echo date('j'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // [Rest of your JavaScript code remains the same]
    </script>
</body>
</html>
