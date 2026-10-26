import importlib.util
import pathlib
import unittest

spec = importlib.util.spec_from_file_location("maritime_route", pathlib.Path(__file__).parents[2] / "scripts" / "maritime_route.py")
router = importlib.util.module_from_spec(spec)
spec.loader.exec_module(router)


class WorldRoutingTest(unittest.TestCase):
    def test_antipodal_distance_is_finite(self):
        self.assertGreater(router.haversine_nm(0, 0, 0, 180), 10000)

    @unittest.skipUnless(router.HAS_COASTAL, "Optional coastal packages unavailable")
    def test_dateline_grid_uses_the_short_pacific_corridor(self):
        origin = {"latitude": 0, "longitude": 179.9}
        destination = {"latitude": 0, "longitude": -179.9}
        grid = router.build_grid(origin, destination, 0.45)
        self.assertLess(grid["rows"] * grid["cols"], 20000)
        start = router.to_cell(grid, origin)
        end = router.to_cell(grid, destination)
        self.assertLess(abs(start[1] - end[1]), 30)


if __name__ == "__main__":
    unittest.main()
