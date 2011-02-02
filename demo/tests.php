<?

# Adapter
$adapter = 'jquery';

?><!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>
		History.js &lt; BrowserState Suite
	</title>
</head>
<body>
	<!-- Scripts -->
	<script type="text/javascript">
		if ( typeof JSON === 'undefined' ) {
			var
				url = '../scripts/uncompressed/json2.js',
				scriptEl = document.createElement('script');
			scriptEl.type = 'text/javascript';
			scriptEl.src = url;
			document.body.appendChild(scriptEl,document.body.firstChild);
		}
	</script>
	<? switch ( $adapter ) :
		case 'jquery':
		case 'prototype':
		case 'mootools': ?>
			<script type="text/javascript" src="./scripts/<?=$adapter?>.js"></script>
			<script type="text/javascript" src="../scripts/uncompressed/history.adapter.<?=$adapter?>.js"></script>
			<? break;
		default:
			throw new Exception('That adapter is not supported!');
	endswitch; ?>
	<script type="text/javascript" src="../scripts/uncompressed/history.js"></script>

	<!-- HTML -->
	<textarea id="log" style="width:100%;height:500px"></textarea>
	<button onclick="javascript:History.back()">back</button>
	<button onclick="javascript:History.forward()">forward</button>
	<button onclick="javascript:alert(document.location.hash)">get hash</button>
	<button onclick="javascript:History.setHash(prompt())">set hash</button>
	<button onclick="javascript:alert(document.location.href)">get location</button>
	<p>Tests Passed: <span id="tests-passed"></span></p>
	<p>Tests Failed: <span id="tests-failed"></span></p>
	<p>Tests Completed: <span id="tests-completed"></span>/<span id="tests-total"></span></p>

	<script type="text/javascript">

		(function(window,undefined){

			var History = window.History;
			History.debug.enable = true;

			var States = {
				// Home
				0: {
					'url': document.location.href.replace(/#.*$/,'')
				},
				// One
				1: {
					'data': {
						'state': 1
					},
					'title': 'State 1',
					'url': '?state=1'
				},
				// Two
				2: {
					'data': {
						'state': 2
					},
					'title': 'State 2',
					'url': '?state=2'
				},
				// Three
				3: {
					'url': '?state=3'
				},
				// Four
				4: {
					'data': {
						'state': 4,
						'trick': true
					},
					'title': 'State 4',
					'url': '?state=3'
				},
				// Log
				5: {
					'url': '?state=1#log'
				}
			};

			var
				testsOrder = [0,1,2,3,4,3,1,0,1,3,4,3,1,0],
				totalTests = testsOrder.length,
				currentTest = 0,
				passedTests = 0,
				failedTests = 0,
				testsCompleted = function(){
					if ( currentTest === testsOrder.length ) {
						History.log('Test suite has finished: '+currentTest+'/'+testsOrder.length+' tests were run');
					}
					else if ( currentTest < testsOrder.length ) {
						History.log('Test suite has finished: '+currentTest+'/'+testsOrder.length+' tests were run: '+(testsOrder.length-currentTest)+' tests were missed');
					}
					else if ( currentTest > testsOrder.length ) {
						History.log('Test suite has finished: '+currentTest+'/'+testsOrder.length+' tests were run: '+(currentTest-testsOrder.length)+' tests were unexpected');
					}
					History.log('Results: '+passedTests+' passed, '+failedTests+' failed');
				};

			History.Adapter.bind(window,'anchorchange',function(){
				History.log('Tests.anchorchange', this, arguments);
			});

			History.Adapter.bind(window,'hashchange',function(){
				History.log('Tests.hashchange', this, arguments);
			});

			History.Adapter.bind(window,'popstate',function(){
				History.log('Tests.popstate', this, arguments);
			});

			History.Adapter.bind(window,'statechange',function(){
				History.log('Tests.statechange', this, arguments);

				var
					state = testsOrder[currentTest],
					expectedState = History.expandState(States[state]),
					actualState = History.getState(),
					expectedStateStr = JSON.stringify(expectedState),
					actualStateStr = JSON.stringify(actualState);

				++currentTest;

				if ( expectedStateStr === actualStateStr ) {
					// test passed
					++passedTests;
					History.log(
						'Test '+(currentTest)+' / State '+state+' passed.',
						{'expected':expectedState,'actual':actualState},
						'expected',expectedStateStr,'actual',actualStateStr,
						'location',document.location.href
					);
				}
				else {
					// test failed
					++failedTests;
					History.log(
						'Test '+(currentTest)+' / State '+state+' FAILED.',
						{'expected':expectedState,'actual':actualState},
						'expected',expectedStateStr,'actual',actualStateStr,
						'location',document.location.href
					);
				}

				var
					$testsPassed = document.getElementById('tests-passed')
					$testsFailed = document.getElementById('tests-failed'),
					$testsCompleted = document.getElementById('tests-completed'),
					$testsTotal = document.getElementById('tests-total');
				$testsTotal.innerHTML = totalTests;
				$testsPassed.innerHTML = (passedTests);
				$testsFailed.innerHTML = (failedTests);
				$testsCompleted.innerHTML = (currentTest);

			});

			var addedTests = 0;
			var addTest = function(test){
				++addedTests;
				setTimeout(function(){
					if ( failedTests ) {
						return false;
					}
					test()
				}, addedTests*History.options.hashChangeCheckerDelay*80);
			};

			History.Adapter.onDomLoad(function(){

				addTest(function(){
					// Test 2 / State 1
					History.setHash(History.contractState(History.expandState(States[1])));
				});

				addTest(function(){
					// Test 3 / State 2
					History.pushState(States[2].data,States[2].title,States[2].url);
				});

				addTest(function(){
					// Test 4 / State 3
					History.replaceState(States[3].data,States[3].title,States[3].url);
				});

				addTest(function(){
					// Test 5 / State 4
					History.pushState(States[4].data,States[4].title,States[4].url);
				});

				addTest(function(){
					// Test 6 / State 1 (4 -> 3)
					// Test 7 / State 1 (3 -> 2 -> 1)
					History.go(-2);
				});

				addTest(function(){
					// Test 8 / State 0 (1 -> 0)
					// Fails in Google Chrome (has state 1's data, instead of no data)
					// Passes in Firefox
					// Passes in HTML4 Browsers
					History.back();
				});

				addTest(function(){
					// Test 9 / State 1 (0 -> 1)
					// Test 10 / State 3 (1 -> 2 -> 3)
					History.go(2);
				});

				addTest(function(){
					// Test 11 / State 4 (3 -> 4)
					History.forward();
				});

				addTest(function(){
					// Test 12 / State 3 (4 -> 3)
					History.back();
				});

				addTest(function(){
					// Test 13 / State 1 (3 -> 2 -> 1)
					History.back();
				});

				addTest(function(){
					// No Change
					History.setHash('log');
				});

				addTest(function(){
					// Traverse Back (#log -> 1)
					// No change, is not logged
					History.back();
				});


				addTest(function(){
					// Test 14 / State 0 (1 -> 0)
					History.back();
				});

				addTest(function(){
					// Tests completed
					testsCompleted();
				});
			});

		})(window);

	</script>

</body></html>