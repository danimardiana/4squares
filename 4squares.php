<?php
/**
 * 4 x 4
 * find accepted list
 * mapping the place of pieces
 * 1.1 1.2 1.3 1.4
 * 2.1 2.2 2.3 2.4
 * 3.1 3.2 3.3 3.4
 * 4.1 4.2 4.3 4.4
 * 
 * available list
 * 1x1
 * 2x1 2x2 2x3 2x4
 * 4x1 4x2 4x3 4x4
 * 
 * sample 1x1 = 1.1
 *              1.2
 *              ...
 * sample 2x2 = 1.1,1.2,2.1,2.2
 *              1.2,1.3,2.2,2.3
 *              1.3,1.4,2.3,2.4
 *              ...
 */

function getPosition($str,$type,$xmax=4,$ymax=4) {
    $s = str_replace(" ","",$str);
    $new_str = strtolower($s);
    list($x,$y) = explode("x",$new_str);

    $pos = array();
    $dot = ".";
    $i = 0;

    switch ($type) {
        case 'head':
            for ($xx=$y; $xx <= $xmax; $xx++) { 
                for ($yy=$x; $yy <= $ymax; $yy++) {
                    $pos[] = ($xx-$y+1).$dot.($yy-$x+1);
                }
                $i++;
            }
            break;
        
        case 'tail':
        default:
            for ($xx=$y; $xx <= $xmax; $xx++) { 
                for ($yy=$x; $yy <= $ymax; $yy++) { 
                    $pos[] = $xx.$dot.$yy;
                }
                $i++;
            }
            break;
    }

    return $pos;
}

function createPosition($str) {
    $arr['head'] = getPosition($str,'head');
    $arr['tail'] = getPosition($str,'tail');
    return $arr;
}

function createBlock($x=4,$y=4) {
    
    for ($i=0; $i < $x; $i++) { 
        for ($j=0; $j < $y; $j++) {
            $index = ($i+1).".".($j+1);
            $block[] = $index;
        }
    }
    
    return $block;
}

function createBlockTable($x=4,$y=4) {
    $table = "<table border=\"1\">";
    $body = "<tbody>";
    $tr = $td = "";
    for ($i=0; $i < $x; $i++) { 
        $tr .= "<tr>";
        for ($j=0; $j < $y; $j++) {
            $index = ($i+1).".".($j+1);
            $td .= "<td style=\"padding: 10px;\">".$index."</td>";
        }
        $tr .= $td . "</tr>";
        $td = "";
    }
    $body .= $tr . "</tbody>";
    $table .= $body . "</table>";
    echo $table;
}

function createBoldBlock($head,$tail,$x=4,$y=4) {
    $table = "<table border=\"1\">";
    $body = "<tbody>";
    $tr = $td = "";
    $strong = "";

    // explode
    list($hx,$hy) = explode(".",$head);
    list($tx,$ty) = explode(".",$tail);
    for ($i=0; $i < $x; $i++) { 
        $tr .= "<tr>";
        for ($j=0; $j < $y; $j++) {
            $ii = ($i+1);
            $jj = ($j+1);
            $index = $ii.".".$jj;

            if ($ii <= $tx && $jj <= $ty) {
                $strong = 'font-weight: bold;';
            } else {
                $strong = '';
            }

            $td .= "<td style=\"padding: 10px; $strong\">".$index."</td>";
        }
        $tr .= $td . "</tr>";
        $td = "";
    }
    $body .= $tr . "</tbody>";
    $table .= $body . "</table>";
    echo $table;
}

function createListBlock($data,$slot,$xmax=4,$ymax=4) {

    $b = createPosition($data);
    $d['head'] = $d['tail'] = null;
    $available = $reserved = null; 

    if (is_array($slot)) {
        // find head
        foreach ($b['head'] as $key => $h) {
            if (in_array($h,$slot)) {
                $d['head'] = $h;
                $d['tail'] = $b['tail'][$key];
                break;
            }
        }
        // find tail
        // foreach ($b['tail'] as $key => $t) {
        //     if (in_array($t,$slot)) {
        //         $d['tail'] = $t;
        //         break;
        //     }
        // }
    } else {
        $d['head'] = $b['head'][0];
        $d['tail'] = $b['tail'][0];
    }

    // means no slot available
    if ($d['head'] == null || $d['tail'] == null) {
        $arr['reserved'] = array();
        $arr['available'] = createBlock();
        $arr['loop'] = true;

        return $arr;
    }

    list($hx,$hy) = explode(".",$d['head']);
    list($tx,$ty) = explode(".",$d['tail']);
    for ($i=0; $i < $ymax; $i++) { 
        for ($j=0; $j < $xmax; $j++) {
            $ii = ($i+1);
            $jj = ($j+1);
            $index = $ii.".".$jj;

            if (($ii >= $hx && $ii <= $tx) && ($jj >= $hy && $jj <= $ty)) {
                $reserved[] = $index;
            } else {
                $available[] = $index;
            }
        }
    }

    $arr['reserved'] = $reserved;
    $arr['available'] = $available;
    $arr['data'] = $data;
    $arr['slot'] = $slot;
    $arr['pos'] = $d;

    return $arr;
}

function arrayDesc($a=array()) {
    arsort($a);
    $new_arr = array();
    foreach ($a as $k => $d) {
        $new_arr[] = $d;
    }

    return $new_arr;
}

function generateBlock($arr_lists) {
    // re-arrange
    $arr = arrayDesc($arr_lists);

    // block
    $i = 1;
    $list = array();
    foreach ($arr as $key => $data) {
        $pos = createPosition($data);
        if ($key == 0) {
            // initial block
            $slot = createListBlock($data,null);
            $list['block'][$i][] = $data;
            $temp = $slot['available'];
        } else {
            if ($slot !== false) {
                $slot = createListBlock($data,$slot['available']);
                $list['block'][$i][] = $data;
            } else {
                $i++; // new block created
                $slot = createListBlock($data,null);
                $list['block'][$i][] = $data;
            }
        }
    }

    return $list;
} 

function slicingBlock($block,$slice) {
    $new_block = array_diff($block,$slice);
    $arr = array();
    foreach ($new_block as $key => $nb) {
        $arr[] = $nb;
    }

    return $arr;
}

function generateBlockX($arr_lists) {
    // re-arrange
    $arr = arrayDesc($arr_lists);

    // block
    $i = 1;
    $list = array();
    $block = createBlock();
    foreach ($arr as $key => $data) {
        $pos = createPosition($data);
        if ($key == 0) {
            // initial block
            $slot = createListBlock($data,null);
            $block = slicingBlock($block,$slot['reserved']);
            $list[$key]['block'][$i] = $data;
            $list[$key]['pos'] = $slot['pos'];
        } else {
            if (sizeof($block) == 0) {
                // create new block
                $i++;
                $block = createBlock();
            }

            $slot = createListBlock($data,$block);
            if (isset($slot['loop'])) {
                // create new block
                $i++;
                $block = createBlock();
                $slot = createListBlock($data,$block);
            }
            $block = slicingBlock($block,$slot['reserved']);
            $list[$key]['block'][$i] = $data;
            $list[$key]['pos'] = $slot['pos'];
        }
    }

    return $list;
}

function dumper($r) {
    echo "<pre>";
    var_dump($r);
    echo "</pre>";
    // exit();
}

/*
* Testing variable
*/
$mission_template = array(
    '4x4',
    '4x3',
    '4x1',
    '2x3',
    '2x3',
    '2x3',
    '2x2',
    '2x2',
    '2x2',
    '2x1',
    '2x1',
    '1x1',
    '1x1',
    '1x1',
    '1x1',
    '1x1',
);

dumper(generateBlockX($mission_template));
// $sample = '4x1';
// dumper(
//     createListBlock($sample,
//         array(
//             '1.1x','1.2x','1.3x','1.4x',
//             '2.1x','2.2x','2.3x','2.4x',
//             '3.1x','3.2x','3.3x','3.4x',
//             '4.1','4.2','4.3','4.4',
//         )
//     )
// );
// dumper(createPosition($sample));
// dumper(createBlock());
// createBoldBlock($head='1.1',$tail='4.4');
createBlockTable();